<?php

namespace App\Services\Auth;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class RegistrationReconciliationService
{
    public function __construct(
        private readonly RegistrationAttemptService $attempts,
        private readonly SignupCodeService $signupCodes
    ) {}

    /**
     * Recover an interrupted registration using
     * an existing, verified Supabase Auth identity.
     *
     * This method never creates another Auth user
     * or another Elsewhere account.
     */
    public function reconcile(int $attemptId): array
    {
        $attempt = $this->attempts->find($attemptId);

        if ($attempt === null) {
            return $this->result(
                'not_found',
                'attempt_not_found'
            );
        }

        if ($attempt->status === 'completed') {
            return $this->result('completed');
        }

        if ($attempt->status !== 'needs_reconciliation') {
            return $this->result(
                'not_eligible',
                'invalid_attempt_status'
            );
        }

        $authId = strtolower(
            trim((string) $attempt->auth_id)
        );

        $email = strtolower(
            trim((string) $attempt->email)
        );

        if (! Str::isUuid($authId)) {
            return $this->result(
                'unresolved',
                'auth_identity_not_claimed'
            );
        }

        if ($email === '') {
            return $this->result(
                'unresolved',
                'invalid_attempt_email'
            );
        }

        /*
         * First verify the actual Supabase Auth user.
         *
         * A provisional UUID claim is not proof that
         * Supabase successfully created the user.
         */
        try {
            $authUser = DB::table('auth.users')
                ->where('id', $authId)
                ->whereRaw('lower(email) = ?', [$email])
                ->select(
                    'id',
                    'email',
                    'raw_user_meta_data'
                )
                ->first();

            if ($authUser === null) {
                return $this->result(
                    'unresolved',
                    'auth_identity_not_found'
                );
            }

            /*
             * Supabase stores signup form metadata in
             * auth.users.raw_user_meta_data.
             *
             * We originally submitted first_name and
             * last_name in the Auth signup request.
             */
            $metadata = $this->parseMetadata(
                $authUser->raw_user_meta_data
            );

            $firstName = $metadata['first_name'] ?? null;
            $lastName = $metadata['last_name'] ?? null;

            if (
                ! is_string($firstName) ||
                ! is_string($lastName)
            ) {
                return $this->result(
                    'unresolved',
                    'identity_metadata_missing'
                );
            }

            $firstName = trim($firstName);
            $lastName = trim($lastName);

            if (
                $firstName === '' ||
                $lastName === '' ||
                mb_strlen($firstName) > 100 ||
                mb_strlen($lastName) > 100
            ) {
                return $this->result(
                    'unresolved',
                    'identity_metadata_invalid'
                );
            }

            /*
             * The existing on_auth_user_created trigger
             * must have generated both account records.
             */
            $account = DB::table(
                'public.accounts_users as au'
            )
                ->join(
                    'public.accounts as a',
                    'a.account_id',
                    '=',
                    'au.account_id'
                )
                ->where('au.auth_id', $authId)
                ->where('a.account_type', 'user')
                ->select('au.account_id')
                ->first();

            if ($account === null) {
                return $this->result(
                    'unresolved',
                    'account_not_provisioned'
                );
            }
        } catch (Throwable $exception) {
            report($exception);

            return $this->result(
                'unresolved',
                'verification_unavailable'
            );
        }

        /*
         * Every finalisation operation runs inside
         * one database transaction.
         *
         * The existing services acquire the parent
         * signup-code lock when changing attempt
         * or reservation state.
         */
        try {
            DB::transaction(function () use (
                $attemptId,
                $authId,
                $email,
                $account,
                $firstName,
                $lastName
            ) {
                /*
                 * Re-read the attempt to ensure it
                 * still belongs to this identity.
                 */
                $current = $this->attempts->find($attemptId);

                if (
                    $current === null ||
                    $current->status !== 'needs_reconciliation' ||
                    strtolower((string) $current->auth_id)
                        !== $authId ||
                    $current->email !== $email
                ) {
                    throw new RuntimeException(
                        'registration_state_changed'
                    );
                }

                /*
                 * Mark Auth creation as confirmed.
                 * This does not create a new user.
                 */
                if (! $this->attempts->confirmAuthCreated(
                    $attemptId,
                    $authId,
                    $email
                )) {
                    throw new RuntimeException(
                        'auth_confirmation_failed'
                    );
                }

                if (! $this->attempts->beginProvisioning(
                    $attemptId
                )) {
                    throw new RuntimeException(
                        'provisioning_start_failed'
                    );
                }

                /*
                 * The account already exists because
                 * the Supabase database trigger created
                 * it during Auth user insertion.
                 *
                 * Restore the names supplied at signup.
                 */
                $updated = DB::table('public.accounts_users')
                    ->where('auth_id', $authId)
                    ->where('account_id', $account->account_id)
                    ->update([
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'updated_at' => now(),
                    ]);

                if ($updated !== 1) {
                    throw new RuntimeException(
                        'identity_update_failed'
                    );
                }

                /*
                 * The original reservation token is
                 * unavailable. Redeem using the already
                 * confirmed registration attempt.
                 */
                if (! $this->signupCodes->redeemByAttempt(
                    $attemptId
                )) {
                    throw new RuntimeException(
                        'invitation_redemption_failed'
                    );
                }

                /*
                 * Completion checks the matching
                 * permanent redemption and reservation.
                 */
                if (! $this->attempts->complete($attemptId)) {
                    throw new RuntimeException(
                        'registration_completion_failed'
                    );
                }
            }, 3);
        } catch (Throwable $exception) {
            report($exception);

            /*
             * Transaction rollback restores the
             * previous needs_reconciliation state
             * and undoes any partial finalisation.
             */
            return $this->result(
                'unresolved',
                'finalisation_failed'
            );
        }

        return $this->result('completed');
    }

    /**
     * Parse Supabase's JSONB user metadata.
     *
     * PostgreSQL may return JSONB as a JSON string
     * through the database connection.
     */
    private function parseMetadata(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_object($value)) {
            return (array) $value;
        }

        if (! is_string($value) || $value === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function result(
        string $status,
        ?string $errorCode = null
    ): array {
        return [
            'status' => $status,
            'error_code' => $errorCode,
        ];
    }
}
