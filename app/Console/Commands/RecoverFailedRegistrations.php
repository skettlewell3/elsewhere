<?php

namespace App\Console\Commands;

use App\Services\Auth\FailedRegistrationRecoveryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class RecoverFailedRegistrations extends Command
{
    protected $signature = 'registration:recover-failed
        {--apply : Apply recovery changes instead of previewing them}';

    protected $description =
        'Recover stale registrations when no Supabase Auth user exists';

    public function handle(
        FailedRegistrationRecoveryService $recovery
    ): int {
        if (! config('registration.recovery_enabled', false)) {
            $this->warn('Automatic registration recovery is disabled.');

            return self::SUCCESS;
        }

        $graceMinutes = max(
            60,
            (int) config('registration.recovery_grace_minutes', 60)
        );

        $attemptIds = DB::table('registration_attempts')
            ->where('status', 'needs_reconciliation')
            ->where('updated_at', '<=', now()->subMinutes($graceMinutes))
            ->orderBy('registration_attempt_id')
            ->limit(50)
            ->pluck('registration_attempt_id');

        if ($attemptIds->isEmpty()) {
            $this->info('No stale registration attempts found.');

            return self::SUCCESS;
        }

        if (! $this->option('apply')) {
            $this->info('Preview only. No records changed.');

            foreach ($attemptIds as $attemptId) {
                $this->line("Candidate attempt: {$attemptId}");
            }

            return self::SUCCESS;
        }

        $recovered = 0;
        $unresolved = 0;

        foreach ($attemptIds as $attemptId) {
            try {
                $result = $recovery->recover((int) $attemptId);

                $this->line("Attempt {$attemptId}: {$result}");

                if ($result === 'recovered') {
                    $recovered++;
                } else {
                    $unresolved++;
                }
            } catch (Throwable $exception) {
                report($exception);

                $this->error(
                    "Attempt {$attemptId}: recovery failed"
                );

                $unresolved++;
            }
        }

        $this->info(
            "Recovered: {$recovered}. Not recovered: {$unresolved}."
        );

        return $unresolved === 0
            ? self::SUCCESS
            : self::FAILURE;
    }
}
