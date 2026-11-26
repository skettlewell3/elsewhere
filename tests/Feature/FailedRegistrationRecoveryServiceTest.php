<?php

namespace Tests\Feature;

use App\Services\Auth\FailedRegistrationRecoveryService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class FailedRegistrationRecoveryServiceTest extends TestCase
{
    private const AUTH_ID = '11111111-1111-4111-8111-111111111111';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        config()->set('registration.recovery_grace_minutes', 60);

        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');

        DB::connection()->statement(
            "ATTACH DATABASE ':memory:' AS auth"
        );

        $this->createTestTables();
        $this->seedAttempt();
    }

    protected function tearDown(): void
    {
        DB::purge('sqlite');

        parent::tearDown();
    }

    private function createTestTables(): void
    {
        Schema::create('signup_codes', function (Blueprint $table) {
            $table->id('signup_code_id');
        });

        Schema::create(
            'signup_code_reservations',
            function (Blueprint $table) {
                $table->id('reservation_id');
                $table->unsignedBigInteger('signup_code_id');
                $table->string('status');
                $table->timestamp('released_at')->nullable();
            }
        );

        Schema::create(
            'registration_attempts',
            function (Blueprint $table) {
                $table->id('registration_attempt_id');
                $table->unsignedBigInteger('reservation_id');
                $table->string('email');
                $table->uuid('auth_id')->nullable();
                $table->string('status');
                $table->string('last_error_code')->nullable();
                $table->timestamp('failed_at')->nullable();
                $table->timestamp('updated_at')->nullable();
            }
        );

        Schema::create(
            'accounts_users',
            function (Blueprint $table) {
                $table->uuid('auth_id')->primary();
            }
        );

        Schema::create(
            'signup_code_redemptions',
            function (Blueprint $table) {
                $table->id('redemption_id');
                $table->unsignedBigInteger('reservation_id');
                $table->uuid('auth_id');
            }
        );

        DB::statement(
            'CREATE TABLE auth.users (
                id TEXT PRIMARY KEY,
                email TEXT NOT NULL
            )'
        );
    }

    private function seedAttempt(): void
    {
        DB::table('signup_codes')->insert([
            'signup_code_id' => 6,
        ]);

        DB::table('signup_code_reservations')->insert([
            'reservation_id' => 7,
            'signup_code_id' => 6,
            'status' => 'reserved',
        ]);

        DB::table('registration_attempts')->insert([
            'registration_attempt_id' => 7,
            'reservation_id' => 7,
            'email' => 'test@example.com',
            'auth_id' => self::AUTH_ID,
            'status' => 'needs_reconciliation',
            'last_error_code' => 'unknown',
            'updated_at' => now()->subHours(2),
        ]);
    }

    private function recover(): string
    {
        return app(FailedRegistrationRecoveryService::class)
            ->recover(7);
    }

    private function assertAttemptUnchanged(): void
    {
        $this->assertDatabaseHas('registration_attempts', [
            'registration_attempt_id' => 7,
            'status' => 'needs_reconciliation',
            'last_error_code' => 'unknown',
        ]);

        $this->assertDatabaseHas('signup_code_reservations', [
            'reservation_id' => 7,
            'status' => 'reserved',
        ]);
    }

    public function test_it_recovers_an_attempt_without_an_auth_user(): void
    {
        $this->assertSame('recovered', $this->recover());

        $this->assertDatabaseHas('registration_attempts', [
            'registration_attempt_id' => 7,
            'status' => 'failed',
            'auth_id' => self::AUTH_ID,
            'last_error_code' => 'auth_identity_not_found_after_grace',
        ]);

        $this->assertDatabaseHas('signup_code_reservations', [
            'reservation_id' => 7,
            'status' => 'released',
        ]);

        $this->assertNotNull(
            DB::table('registration_attempts')
                ->where('registration_attempt_id', 7)
                ->value('failed_at')
        );

        $this->assertNotNull(
            DB::table('signup_code_reservations')
                ->where('reservation_id', 7)
                ->value('released_at')
        );
    }

    public function test_it_preserves_an_attempt_when_auth_user_exists(): void
    {
        DB::table('auth.users')->insert([
            'id' => self::AUTH_ID,
            'email' => 'test@example.com',
        ]);

        $this->assertSame('auth_exists', $this->recover());

        $this->assertAttemptUnchanged();
    }

    public function test_it_preserves_an_attempt_when_email_is_registered(): void
    {
        DB::table('auth.users')->insert([
            'id' => '22222222-2222-4222-8222-222222222222',
            'email' => 'test@example.com',
        ]);

        $this->assertSame('auth_exists', $this->recover());

        $this->assertAttemptUnchanged();
    }

    public function test_it_preserves_an_attempt_when_elsewhere_account_exists(): void
    {
        DB::table('accounts_users')->insert([
            'auth_id' => self::AUTH_ID,
        ]);

        $this->assertSame('account_exists', $this->recover());

        $this->assertAttemptUnchanged();
    }

    public function test_it_preserves_an_already_redeemed_invitation(): void
    {
        DB::table('signup_code_redemptions')->insert([
            'reservation_id' => 7,
            'auth_id' => self::AUTH_ID,
        ]);

        $this->assertSame('already_redeemed', $this->recover());

        $this->assertAttemptUnchanged();
    }

    public function test_it_does_not_recover_a_recent_attempt(): void
    {
        DB::table('registration_attempts')
            ->where('registration_attempt_id', 7)
            ->update([
                'updated_at' => now(),
            ]);

        $this->assertSame('too_recent', $this->recover());

        $this->assertAttemptUnchanged();
    }

    public function test_recovery_cannot_be_applied_twice(): void
    {
        $this->assertSame('recovered', $this->recover());

        $this->assertSame('not_eligible', $this->recover());

        $this->assertDatabaseHas('registration_attempts', [
            'registration_attempt_id' => 7,
            'status' => 'failed',
        ]);

        $this->assertDatabaseHas('signup_code_reservations', [
            'reservation_id' => 7,
            'status' => 'released',
        ]);

        $this->assertDatabaseCount(
            'signup_code_redemptions',
            0
        );
    }
}
