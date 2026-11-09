<?php

namespace App\Services\Auth;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RegistrationAttemptService
{
    public function create(
        string $reservationToken,
        string $email
    ): ?int {
        $email = strtolower(trim($email));

        if ($reservationToken === '' || $email === '') {
            return null;
        }

        $tokenHash = hash('sha256', $reservationToken);

        return DB::transaction(function () use ($tokenHash, $email) {
            $reservation = DB::table('signup_code_reservations')
                ->where('token_hash', $tokenHash)
                ->first();

            if (! $reservation) {
                return null;
            }

            // Lock the parent code first, consistently
            // with SignupCodeService.

            $signupCode = DB::table('signup_codes')
                ->where(
                    'signup_code_id',
                    $reservation->signup_code_id
                )
                ->lockForUpdate()
                ->first();

            if (! $signupCode) {
                return null;
            }

            $reservation = DB::table('signup_code_reservations')
                ->where(
                    'reservation_id',
                    $reservation->reservation_id
                )
                ->where('token_hash', $tokenHash)
                ->first();

            if (
                ! $reservation ||
                $reservation->email !== $email ||
                $reservation->status !== 'reserved' ||
                ! Carbon::parse($reservation->expires_at)->isFuture()
            ) {
                return null;
            }

            $existing = DB::table('registration_attempts')
                ->where(
                    'reservation_id',
                    $reservation->reservation_id
                )
                ->first();

            if ($existing) {
                return null;
            }

            return DB::table('registration_attempts')
                ->insertGetId([
                    'reservation_id' => $reservation->reservation_id,
                    'email' => $email,
                    'status' => 'reserved',
                    'created_at' => now(),
                    'updated_at' => now(),
                ], 'registration_attempt_id');
        }, 3);
    }

    public function beginAuthCreation(int $attemptId): bool
    {
        return $this->transition(
            $attemptId,
            ['reserved'],
            'creating_auth',
            [
                'auth_requested_at' => now(),
            ]
        );
    }

    public function confirmAuthCreated(
        int $attemptId,
        string $authId,
        string $email
    ): bool {
        $authId = strtolower(trim($authId));
        $email = strtolower(trim($email));

        if (! Str::isUuid($authId) || $email === '') {
            return false;
        }

        return $this->withLockedAttempt(
            $attemptId,
            function ($attempt) use ($attemptId, $authId, $email) {
                if ($attempt->email !== $email) {
                    return false;
                }

                if (
                    $attempt->auth_id !== null &&
                    strtolower($attempt->auth_id) !== $authId
                ) {
                    return false;
                }

                if (
                    $attempt->status === 'auth_created' &&
                    strtolower((string) $attempt->auth_id) === $authId
                ) {
                    return true;
                }

                if (! in_array(
                    $attempt->status,
                    ['creating_auth', 'needs_reconciliation'],
                    true
                )) {
                    return false;
                }

                return DB::table('registration_attempts')
                    ->where(
                        'registration_attempt_id',
                        $attemptId
                    )
                    ->update([
                        'auth_id' => $authId,
                        'status' => 'auth_created',
                        'auth_created_at' =>
                            $attempt->auth_created_at ?? now(),
                        'last_error_code' => null,
                        'updated_at' => now(),
                    ]) === 1;
            }
        );
    }

    public function beginProvisioning(int $attemptId): bool
    {
        return $this->withLockedAttempt(
            $attemptId,
            function ($attempt) use ($attemptId) {
                if (
                    $attempt->status !== 'auth_created' ||
                    $attempt->auth_id === null
                ) {
                    return false;
                }

                return DB::table('registration_attempts')
                    ->where(
                        'registration_attempt_id',
                        $attemptId
                    )
                    ->update([
                        'status' => 'provisioning',
                        'updated_at' => now(),
                    ]) === 1;
            }
        );
    }

    public function complete(int $attemptId): bool
    {
        return $this->withLockedAttempt(
            $attemptId,
            function ($attempt) use ($attemptId) {
                if ($attempt->status === 'completed') {
                    return true;
                }

                if (
                    ! in_array(
                        $attempt->status,
                        ['provisioning', 'needs_reconciliation'],
                        true
                    ) ||
                    $attempt->auth_id === null
                ) {
                    return false;
                }

                // Completion requires a matching permanent redemption.

                $redeemed = DB::table('signup_code_redemptions')
                    ->where('reservation_id', $attempt->reservation_id)
                    ->where('auth_id', $attempt->auth_id)
                    ->exists();

                $reservationRedeemed = DB::table(
                    'signup_code_reservations'
                )
                    ->where('reservation_id', $attempt->reservation_id)
                    ->where('status', 'redeemed')
                    ->exists();

                if (! $redeemed || ! $reservationRedeemed) {
                    return false;
                }

                // RegistrationService must have successfully
                // provisioned the Elsewhere account before
                // calling complete().

                return DB::table('registration_attempts')
                    ->where(
                        'registration_attempt_id',
                        $attemptId
                    )
                    ->update([
                        'status' => 'completed',
                        'provisioned_at' =>
                            $attempt->provisioned_at ?? now(),
                        'completed_at' => now(),
                        'last_error_code' => null,
                        'updated_at' => now(),
                    ]) === 1;
            }
        );
    }

    public function needsReconciliation(
        int $attemptId,
        string $errorCode
    ): bool {
        return $this->transition(
            $attemptId,
            ['creating_auth', 'auth_created', 'provisioning'],
            'needs_reconciliation',
            [
                'last_error_code' => substr($errorCode, 0, 100),
            ]
        );
    }

    public function failBeforeAuthCreation(
        int $attemptId,
        string $errorCode
    ): bool {
        return $this->transition(
            $attemptId,
            ['reserved'],
            'failed',
            [
                'failed_at' => now(),
                'last_error_code' => substr($errorCode, 0, 100),
            ]
        );
    }

    public function find(int $attemptId): ?object
    {
        return DB::table('registration_attempts')
            ->where('registration_attempt_id', $attemptId)
            ->first();
    }

    private function transition(
        int $attemptId,
        array $allowedStatuses,
        string $newStatus,
        array $attributes = []
    ): bool {
        return $this->withLockedAttempt(
            $attemptId,
            function ($attempt) use (
                $attemptId,
                $allowedStatuses,
                $newStatus,
                $attributes
            ) {
                if (! in_array(
                    $attempt->status,
                    $allowedStatuses,
                    true
                )) {
                    return false;
                }

                return DB::table('registration_attempts')
                    ->where(
                        'registration_attempt_id',
                        $attemptId
                    )
                    ->update(array_merge($attributes, [
                        'status' => $newStatus,
                        'updated_at' => now(),
                    ])) === 1;
            }
        );
    }

    private function withLockedAttempt(
        int $attemptId,
        callable $callback
    ): bool {
        return DB::transaction(function () use (
            $attemptId,
            $callback
        ) {
            // Initial lookup only identifies the parent code.

            $attempt = DB::table('registration_attempts')
                ->where('registration_attempt_id', $attemptId)
                ->first();

            if (! $attempt) {
                return false;
            }

            $reservation = DB::table('signup_code_reservations')
                ->where('reservation_id', $attempt->reservation_id)
                ->first();

            if (! $reservation) {
                return false;
            }

            // Every transition locks the parent code first.

            $signupCode = DB::table('signup_codes')
                ->where(
                    'signup_code_id',
                    $reservation->signup_code_id
                )
                ->lockForUpdate()
                ->first();

            if (! $signupCode) {
                return false;
            }

            // Re-read the attempt after acquiring the lock.

            $attempt = DB::table('registration_attempts')
                ->where('registration_attempt_id', $attemptId)
                ->where(
                    'reservation_id',
                    $reservation->reservation_id
                )
                ->first();

            if (! $attempt) {
                return false;
            }

            return (bool) $callback($attempt);
        }, 3);
    }
}
