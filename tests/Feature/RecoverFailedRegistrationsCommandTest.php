<?php

namespace Tests\Feature;

use App\Services\Auth\FailedRegistrationRecoveryService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class RecoverFailedRegistrationsCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');

        config()->set('registration.recovery_enabled', false);
        config()->set('registration.recovery_grace_minutes', 60);

        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');

        Schema::create('registration_attempts', function (Blueprint $table) {
            $table->id('registration_attempt_id');
            $table->string('status');
            $table->timestamp('updated_at')->nullable();
        });

        DB::table('registration_attempts')->insert([
            'registration_attempt_id' => 7,
            'status' => 'needs_reconciliation',
            'updated_at' => now()->subHours(2),
        ]);
    }

    protected function tearDown(): void
    {
        DB::purge('sqlite');

        parent::tearDown();
    }

    public function test_command_does_nothing_when_recovery_is_disabled(): void
    {
        $this->artisan('registration:recover-failed', [
            '--apply' => true,
        ])
            ->expectsOutput(
                'Automatic registration recovery is disabled.'
            )
            ->assertExitCode(0);

        $this->assertDatabaseHas('registration_attempts', [
            'registration_attempt_id' => 7,
            'status' => 'needs_reconciliation',
        ]);
    }

    public function test_preview_lists_candidates_without_recovering(): void
    {
        config()->set('registration.recovery_enabled', true);

        $recovery = Mockery::mock(
            FailedRegistrationRecoveryService::class
        );

        $recovery->shouldNotReceive('recover');

        $this->app->instance(
            FailedRegistrationRecoveryService::class,
            $recovery
        );

        $this->artisan('registration:recover-failed')
            ->expectsOutput('Preview only. No records changed.')
            ->expectsOutput('Candidate attempt: 7')
            ->assertExitCode(0);

        $this->assertDatabaseHas('registration_attempts', [
            'registration_attempt_id' => 7,
            'status' => 'needs_reconciliation',
        ]);
    }

    public function test_apply_invokes_recovery_for_stale_attempt(): void
    {
        config()->set('registration.recovery_enabled', true);

        $recovery = Mockery::mock(
            FailedRegistrationRecoveryService::class
        );

        $recovery
            ->shouldReceive('recover')
            ->once()
            ->with(7)
            ->andReturn('recovered');

        $this->app->instance(
            FailedRegistrationRecoveryService::class,
            $recovery
        );

        $this->artisan('registration:recover-failed', [
            '--apply' => true,
        ])
            ->expectsOutput('Attempt 7: recovered')
            ->expectsOutput('Recovered: 1. Not recovered: 0.')
            ->assertExitCode(0);
    }

    public function test_unresolved_attempt_causes_failure_exit_code(): void
    {
        config()->set('registration.recovery_enabled', true);

        $recovery = Mockery::mock(
            FailedRegistrationRecoveryService::class
        );

        $recovery
            ->shouldReceive('recover')
            ->once()
            ->with(7)
            ->andReturn('auth_exists');

        $this->app->instance(
            FailedRegistrationRecoveryService::class,
            $recovery
        );

        $this->artisan('registration:recover-failed', [
            '--apply' => true,
        ])
            ->expectsOutput('Attempt 7: auth_exists')
            ->expectsOutput('Recovered: 0. Not recovered: 1.')
            ->assertExitCode(1);
    }

    public function test_recent_attempt_is_not_selected(): void
    {
        config()->set('registration.recovery_enabled', true);

        DB::table('registration_attempts')
            ->where('registration_attempt_id', 7)
            ->update([
                'updated_at' => now(),
            ]);

        $recovery = Mockery::mock(
            FailedRegistrationRecoveryService::class
        );

        $recovery->shouldNotReceive('recover');

        $this->app->instance(
            FailedRegistrationRecoveryService::class,
            $recovery
        );

        $this->artisan('registration:recover-failed', [
            '--apply' => true,
        ])
            ->expectsOutput('No stale registration attempts found.')
            ->assertExitCode(0);
    }
}
