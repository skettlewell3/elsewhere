<?php

namespace App\Services\Auth;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class FailedRegistrationRecoveryService
{
    public function recover(int $attemptId): string
    {
        return DB::transaction(function () use ($attemptId): string {
            $attempt = DB::table('registration_attempts')
                ->where('registration_attempt_id', $attemptId)
                ->first();

            if (! $attempt) {
                return 'not_found';
            }

            $reservation = DB::table('signup_code_reservations')
                ->where('reservation_id', $attempt->reservation_id)
                ->first();

            if (! $reservation) {
                return 'unresolved';
            }

            // Follow the existing services' lock order.
            $code = DB::table('signup_codes')
                ->where('signup_code_id', $reservation->signup_code_id)
                ->lockForUpdate()
                ->first();

            if (! $code) {
                return 'unresolved';
            }

            $attempt = DB::table('registration_attempts')
                ->where('registration_attempt_id', $attemptId)
                ->lockForUpdate()
                ->first();

            $reservation = DB::table('signup_code_reservations')
                ->where('reservation_id', $reservation->reservation_id)
                ->lockForUpdate()
                ->first();

            if (
                ! $attempt ||
                ! $reservation ||
                $attempt->reservation_id !== $reservation->reservation_id ||
                $reservation->signup_code_id !== $code->signup_code_id
            ) {
                return 'unresolved';
            }

            if ($attempt->status !== 'needs_reconciliation') {
                return 'not_eligible';
            }

            if ($reservation->status !== 'reserved') {
                return 'unresolved';
            }

            $graceMinutes = max(
                60,
                (int) config('registration.recovery_grace_minutes', 60)
            );

            if (
                $attempt->updated_at === null ||
                Carbon::parse($attempt->updated_at)
                    ->greaterThan(now()->subMinutes($graceMinutes))
            ) {
                return 'too_recent';
            }

            $authId = strtolower(trim((string) $attempt->auth_id));
            $email = strtolower(trim((string) $attempt->email));

            if (! Str::isUuid($authId) || $email === '') {
                return 'unresolved';
            }

            // Do not close any attempt that might have
            // resulted in a real Supabase identity.
            $authExists = DB::table('auth.users')
                ->where(function ($query) use ($authId, $email) {
                    $query->where('id', $authId)
                        ->orWhereRaw('lower(email) = ?', [$email]);
                })
                ->exists();

            if ($authExists) {
                return 'auth_exists';
            }

            $accountExists = DB::table('accounts_users')
                ->where('auth_id', $authId)
                ->exists();

            if ($accountExists) {
                return 'account_exists';
            }

            $redeemed = DB::table('signup_code_redemptions')
                ->where(function ($query) use ($reservation, $authId) {
                    $query->where('reservation_id', $reservation->reservation_id)
                        ->orWhere('auth_id', $authId);
                })
                ->exists();

            if ($redeemed) {
                return 'already_redeemed';
            }

            $updatedAttempt = DB::table('registration_attempts')
                ->where('registration_attempt_id', $attemptId)
                ->where('status', 'needs_reconciliation')
                ->update([
                    'status' => 'failed',
                    'failed_at' => now(),
                    'last_error_code' => 'auth_identity_not_found_after_grace',
                    'updated_at' => now(),
                ]);

            if ($updatedAttempt !== 1) {
                throw new \RuntimeException(
                    'Failed to update registration attempt.'
                );
            }

            $updatedReservation = DB::table('signup_code_reservations')
                ->where('reservation_id', $reservation->reservation_id)
                ->where('status', 'reserved')
                ->update([
                    'status' => 'released',
                    'released_at' => now(),
                ]);

            if ($updatedReservation !== 1) {
                throw new \RuntimeException(
                    'Failed to release signup code reservation.'
                );
            }

            return 'recovered';
        }, 3);
    }
}
