<?php

namespace App\Services\Auth;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SignupCodeService
{
    private const RESERVATION_MINUTES = 10;

    private const PROTECTED_STATUSES = [
        'creating_auth',
        'auth_created',
        'provisioning',
        'needs_reconciliation',
    ];

    public function isValid(string $code): bool
    {
        $code = trim($code);

        if ($code === '') {
            return false;
        }

        return DB::table('signup_codes')
            ->where('code', $code)
            ->where('active', true)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->where(function ($query) {
                $query->whereNull('max_uses')
                    ->orWhereColumn('use_count', '<', 'max_uses');
            })
            ->exists();
    }

    public function reserve(
        string $code,
        string $email
    ): ?string {
        $code = trim($code);
        $email = strtolower(trim($email));

        if ($code === '' || $email === '') {
            return null;
        }

        return DB::transaction(function () use ($code, $email) {
            $signupCode = DB::table('signup_codes')
                ->where('code', $code)
                ->lockForUpdate()
                ->first();

            if (! $signupCode || ! $signupCode->active) {
                return null;
            }

            if (
                $signupCode->expires_at !== null &&
                ! Carbon::parse($signupCode->expires_at)->isFuture()
            ) {
                return null;
            }

            if ($signupCode->max_uses !== null) {
                $activeReservations = DB::table(
                    'signup_code_reservations as r'
                )
                    ->where(
                        'r.signup_code_id',
                        $signupCode->signup_code_id
                    )
                    ->where('r.status', 'reserved')
                    ->where(function ($query) {
                        $query
                            ->where('r.expires_at', '>', now())
                            ->orWhereExists(function ($subquery) {
                                $subquery
                                    ->selectRaw('1')
                                    ->from('registration_attempts as a')
                                    ->whereColumn(
                                        'a.reservation_id',
                                        'r.reservation_id'
                                    )
                                    ->whereIn(
                                        'a.status',
                                        self::PROTECTED_STATUSES
                                    );
                            });
                    })
                    ->count();

                $occupied = (int) $signupCode->use_count
                    + $activeReservations;

                if ($occupied >= $signupCode->max_uses) {
                    return null;
                }
            }

            $token = bin2hex(random_bytes(32));

            DB::table('signup_code_reservations')
                ->insert([
                    'signup_code_id' =>
                        $signupCode->signup_code_id,

                    'token_hash' => hash('sha256', $token),

                    'email' => $email,

                    'status' => 'reserved',

                    'expires_at' => now()->addMinutes(
                        self::RESERVATION_MINUTES
                    ),

                    'created_at' => now(),
                ]);

            return $token;
        }, 3);
    }

    public function release(string $token): bool
    {
        if ($token === '') {
            return false;
        }

        $tokenHash = hash('sha256', $token);

        return DB::transaction(function () use ($tokenHash) {
            $reservation = DB::table(
                'signup_code_reservations'
            )
                ->where('token_hash', $tokenHash)
                ->first();

            if (! $reservation) {
                return false;
            }

            // Lock the parent code first.

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

            // Re-read the reservation after acquiring the lock.

            $reservation = DB::table(
                'signup_code_reservations'
            )
                ->where(
                    'reservation_id',
                    $reservation->reservation_id
                )
                ->where('token_hash', $tokenHash)
                ->first();

            if (
                ! $reservation ||
                $reservation->status !== 'reserved'
            ) {
                return false;
            }

            // An unresolved registration must retain its capacity.

            $protectedAttempt = DB::table('registration_attempts')
                ->where(
                    'reservation_id',
                    $reservation->reservation_id
                )
                ->whereIn(
                    'status',
                    self::PROTECTED_STATUSES
                )
                ->exists();

            if ($protectedAttempt) {
                return false;
            }

            return DB::table('signup_code_reservations')
                ->where(
                    'reservation_id',
                    $reservation->reservation_id
                )
                ->where('status', 'reserved')
                ->update([
                    'status' => 'released',
                    'released_at' => now(),
                ]) === 1;
        }, 3);
    }

    public function redeem(
        string $token,
        string $authId,
        string $email
    ): bool {
        $authId = strtolower(trim($authId));
        $email = strtolower(trim($email));

        if (
            $token === '' ||
            $authId === '' ||
            $email === '' ||
            ! Str::isUuid($authId)
        ) {
            return false;
        }

        $tokenHash = hash('sha256', $token);

        return DB::transaction(function () use (
            $tokenHash,
            $authId,
            $email
        ) {
            $reservation = DB::table(
                'signup_code_reservations'
            )
                ->where('token_hash', $tokenHash)
                ->first();

            if (! $reservation) {
                return false;
            }

            // Always lock the parent signup code first.

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

            // Re-read the reservation under the parent lock.

            $reservation = DB::table(
                'signup_code_reservations'
            )
                ->where(
                    'reservation_id',
                    $reservation->reservation_id
                )
                ->where('token_hash', $tokenHash)
                ->first();

            if (
                ! $reservation ||
                $reservation->email !== $email
            ) {
                return false;
            }

            // Exact retries must not increment use_count again.

            $existingRedemption = DB::table(
                'signup_code_redemptions'
            )
                ->where(
                    'reservation_id',
                    $reservation->reservation_id
                )
                ->first();

            if ($existingRedemption) {
                return (
                    $reservation->status === 'redeemed' &&
                    (int) $existingRedemption->signup_code_id ===
                        (int) $signupCode->signup_code_id &&
                    strtolower($existingRedemption->auth_id) === $authId
                );
            }

            if ($reservation->status !== 'reserved') {
                return false;
            }

            // A verified registration attempt is mandatory.
            // A reservation token alone cannot be redeemed.

            $attempt = DB::table('registration_attempts')
                ->where(
                    'reservation_id',
                    $reservation->reservation_id
                )
                ->first();

            if (! $attempt) {
                return false;
            }

            if (
                $attempt->email !== $email ||
                strtolower((string) $attempt->auth_id) !== $authId
            ) {
                return false;
            }

            $authConfirmed = (
                $attempt->auth_created_at !== null &&
                in_array(
                    $attempt->status,
                    [
                        'auth_created',
                        'provisioning',
                        'needs_reconciliation',
                    ],
                    true
                )
            );

            if (! $authConfirmed) {
                return false;
            }

            // An expired reservation can only be finalised
            // when the matching Auth identity is confirmed.

            $expired = ! Carbon::parse(
                $reservation->expires_at
            )->isFuture();

            if ($expired && ! $authConfirmed) {
                return false;
            }

            // A Supabase identity can redeem only one invitation.

            $authAlreadyRedeemed = DB::table(
                'signup_code_redemptions'
            )
                ->where('auth_id', $authId)
                ->exists();

            if ($authAlreadyRedeemed) {
                return false;
            }

            // Record the permanent redemption.

            DB::table('signup_code_redemptions')
                ->insert([
                    'signup_code_id' =>
                        $signupCode->signup_code_id,

                    'reservation_id' =>
                        $reservation->reservation_id,

                    'auth_id' => $authId,

                    'redeemed_at' => now(),
                ]);

            // Consume one invitation use.

            DB::table('signup_codes')
                ->where(
                    'signup_code_id',
                    $signupCode->signup_code_id
                )
                ->increment('use_count');

            // Finalise the reservation in the same transaction.

            $updated = DB::table('signup_code_reservations')
                ->where(
                    'reservation_id',
                    $reservation->reservation_id
                )
                ->where('status', 'reserved')
                ->update([
                    'status' => 'redeemed',
                ]);

            if ($updated !== 1) {
                throw new \RuntimeException(
                    'Failed to finalise signup code reservation.'
                );
            }

            return true;
        }, 3);
    }
}
