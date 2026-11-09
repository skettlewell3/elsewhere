<?php

namespace Tests\Feature\Auth;

use App\Services\Auth\RegistrationAttemptService;
use App\Services\Auth\RegistrationService;
use App\Services\Auth\SignupCodeService;
use App\Services\Auth\SupabaseAuthService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class RegistrationServiceTest extends TestCase
{
    private const AUTH_ID =
        '11111111-1111-4111-8111-111111111111';

    private const OTHER_AUTH_ID =
        '22222222-2222-4222-8222-222222222222';

    private const ACCOUNT_ID =
        'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::getDriverName() !== 'sqlite') {
            $this->markTestSkipped(
                'These isolated tests require SQLite.'
            );
        }

        DB::statement("ATTACH DATABASE ':memory:' AS auth");
        DB::statement("ATTACH DATABASE ':memory:' AS public");

        $this->createSignupTables();
        $this->createAccountTables();

        DB::table('signup_codes')->insert([
            'code' => 'TEST-CODE',
            'active' => true,
            'max_uses' => 1,
            'use_count' => 0,
            'created_at' => now(),
        ]);
    }

    protected function tearDown(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            Schema::dropIfExists('registration_attempts');
            Schema::dropIfExists('signup_code_redemptions');
            Schema::dropIfExists('signup_code_reservations');
            Schema::dropIfExists('signup_codes');
            Schema::dropIfExists('accounts_users');

            DB::statement('DROP TABLE IF EXISTS public.accounts_users');
            DB::statement('DROP TABLE IF EXISTS public.accounts');
            DB::statement('DROP TABLE IF EXISTS auth.users');

            DB::statement('DETACH DATABASE public');
            DB::statement('DETACH DATABASE auth');
        }

        parent::tearDown();
    }

    private function createSignupTables(): void
    {
        Schema::create('signup_codes', function (Blueprint $table) {
            $table->id('signup_code_id');
            $table->text('code')->unique();
            $table->text('description')->nullable();
            $table->boolean('active')->default(true);
            $table->integer('max_uses')->nullable();
            $table->integer('use_count')->default(0);
            $table->timestampTz('expires_at')->nullable();
            $table->timestampTz('created_at')->useCurrent();
        });

        Schema::create(
            'signup_code_reservations',
            function (Blueprint $table) {
                $table->id('reservation_id');

                $table->foreignId('signup_code_id')
                    ->constrained(
                        'signup_codes',
                        'signup_code_id'
                    )
                    ->restrictOnDelete();

                $table->string('token_hash', 64)->unique();
                $table->string('email', 255);
                $table->string('status', 16)->default('reserved');
                $table->timestampTz('expires_at');
                $table->timestampTz('created_at')->useCurrent();
                $table->timestampTz('released_at')->nullable();
            }
        );

        Schema::create(
            'signup_code_redemptions',
            function (Blueprint $table) {
                $table->id('redemption_id');

                $table->foreignId('signup_code_id')
                    ->constrained(
                        'signup_codes',
                        'signup_code_id'
                    )
                    ->restrictOnDelete();

                $table->foreignId('reservation_id')
                    ->unique()
                    ->constrained(
                        'signup_code_reservations',
                        'reservation_id'
                    )
                    ->restrictOnDelete();

                $table->uuid('auth_id')->unique();
                $table->timestampTz('redeemed_at')->useCurrent();
            }
        );

        Schema::create(
            'registration_attempts',
            function (Blueprint $table) {
                $table->id('registration_attempt_id');

                $table->foreignId('reservation_id')
                    ->unique()
                    ->constrained(
                        'signup_code_reservations',
                        'reservation_id'
                    )
                    ->restrictOnDelete();

                $table->uuid('auth_id')
                    ->nullable()
                    ->unique();

                $table->string('email', 255);
                $table->string('status', 32)->default('reserved');
                $table->timestampTz('auth_requested_at')->nullable();
                $table->timestampTz('auth_created_at')->nullable();
                $table->timestampTz('provisioned_at')->nullable();
                $table->timestampTz('completed_at')->nullable();
                $table->timestampTz('failed_at')->nullable();
                $table->string('last_error_code', 100)->nullable();
                $table->timestampsTz();
            }
        );
    }

    private function createAccountTables(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE auth.users (
    id TEXT PRIMARY KEY,
    email TEXT NOT NULL
)
SQL);

        DB::statement(<<<'SQL'
CREATE TABLE public.accounts (
    account_id TEXT PRIMARY KEY,
    account_type TEXT NOT NULL
)
SQL);

        DB::statement(<<<'SQL'
CREATE TABLE public.accounts_users (
    auth_id TEXT PRIMARY KEY,
    account_id TEXT NOT NULL,
    username TEXT NOT NULL
)
SQL);

        Schema::create('accounts_users', function (Blueprint $table) {
            $table->uuid('auth_id')->primary();
            $table->uuid('account_id');
            $table->string('username', 32);
            $table->string('first_name', 100)->nullable();
            $table->string('last_name', 100)->nullable();
            $table->timestampsTz();
        });
    }

    /**
     * Simulate an Auth user created by Supabase and
     * the Elsewhere account created by its trigger.
     *
     * The public-schema copies satisfy the explicit
     * public.accounts joins in RegistrationService.
     *
     * The main-schema copy supports Laravel's
     * unqualified accounts_users update.
     */
    private function createAuthAndAccount(
        string $authId = self::AUTH_ID,
        string $email = 'user@example.com',
        bool $createAccount = true
    ): void {
        DB::table('auth.users')->insert([
            'id' => $authId,
            'email' => $email,
        ]);

        if (! $createAccount) {
            return;
        }

        DB::table('public.accounts')->insert([
            'account_id' => self::ACCOUNT_ID,
            'account_type' => 'user',
        ]);

        DB::table('public.accounts_users')->insert([
            'auth_id' => $authId,
            'account_id' => self::ACCOUNT_ID,
            'username' => 'user_aaaaaaaa',
        ]);

        DB::table('accounts_users')->insert([
            'auth_id' => $authId,
            'account_id' => self::ACCOUNT_ID,
            'username' => 'user_aaaaaaaa',
            'first_name' => null,
            'last_name' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Simulate the UUID claim performed by the
     * Before User Created PostgreSQL hook.
     */
    private function claimAuthId(
        string $authId = self::AUTH_ID
    ): void {
        DB::table('registration_attempts')
            ->where('status', 'creating_auth')
            ->update([
                'auth_id' => $authId,
                'updated_at' => now(),
            ]);
    }

    private function mockSignup(callable $callback): void
    {
        $mock = Mockery::mock(SupabaseAuthService::class);

        $mock->shouldReceive('signUp')
            ->once()
            ->with(
                'user@example.com',
                'example-password',
                'First',
                'Last',
                Mockery::type('string')
            )
            ->andReturnUsing($callback);

        $this->app->instance(
            SupabaseAuthService::class,
            $mock
        );
    }

    private function register(
        string $code = 'TEST-CODE'
    ): array {
        return app(RegistrationService::class)->register(
            firstName: 'First',
            lastName: 'Last',
            email: 'user@example.com',
            password: 'example-password',
            signupCode: $code
        );
    }

    public function test_invalid_invitation_rejects_before_supabase(): void
    {
        $result = $this->register('INVALID-CODE');

        $this->assertSame('rejected', $result['status']);
        $this->assertSame(
            'invalid_signup_code',
            $result['error_code']
        );
        $this->assertNull($result['attempt_id']);

        $this->assertSame(
            0,
            DB::table('registration_attempts')->count()
        );

        $this->assertSame(
            0,
            DB::table('signup_code_reservations')->count()
        );
    }

    public function test_successful_registration_completes_all_records(): void
    {
        $this->mockSignup(function (
            string $email,
            string $password,
            string $firstName,
            string $lastName,
            string $token
        ): array {
            $this->claimAuthId();
            $this->createAuthAndAccount();

            return [
                'outcome' => 'response_received',
                'auth_id' => self::AUTH_ID,
                'error_code' => null,
            ];
        });

        $result = $this->register();

        $this->assertSame('completed', $result['status']);
        $this->assertNull($result['error_code']);
        $this->assertNotNull($result['attempt_id']);

        $this->assertDatabaseHas('registration_attempts', [
            'registration_attempt_id' => $result['attempt_id'],
            'auth_id' => self::AUTH_ID,
            'status' => 'completed',
        ]);

        $attempt = DB::table('registration_attempts')
            ->where(
                'registration_attempt_id',
                $result['attempt_id']
            )
            ->first();

        $this->assertNotNull($attempt->auth_created_at);
        $this->assertNotNull($attempt->provisioned_at);
        $this->assertNotNull($attempt->completed_at);

        $this->assertDatabaseHas('accounts_users', [
            'auth_id' => self::AUTH_ID,
            'account_id' => self::ACCOUNT_ID,
            'first_name' => 'First',
            'last_name' => 'Last',
        ]);

        $this->assertDatabaseHas('signup_code_reservations', [
            'status' => 'redeemed',
        ]);

        $this->assertDatabaseHas('signup_code_redemptions', [
            'auth_id' => self::AUTH_ID,
        ]);

        $this->assertSame(
            1,
            DB::table('signup_codes')->value('use_count')
        );

        $this->assertNull(
            app(SignupCodeService::class)->reserve(
                'TEST-CODE',
                'another@example.com'
            )
        );
    }

    public function test_uncertain_supabase_response_preserves_reservation(): void
    {
        $this->mockSignup(function (): array {
            return [
                'outcome' => 'uncertain',
                'auth_id' => null,
                'error_code' => 'connection_failure',
            ];
        });

        $result = $this->register();

        $this->assertSame(
            'needs_reconciliation',
            $result['status']
        );

        $this->assertSame(
            'connection_failure',
            $result['error_code']
        );

        $this->assertDatabaseHas('registration_attempts', [
            'status' => 'needs_reconciliation',
            'last_error_code' => 'connection_failure',
        ]);

        $this->assertDatabaseHas('signup_code_reservations', [
            'status' => 'reserved',
        ]);

        $this->assertSame(
            0,
            DB::table('signup_code_redemptions')->count()
        );

        $this->assertNull(
            app(SignupCodeService::class)->reserve(
                'TEST-CODE',
                'another@example.com'
            )
        );
    }

    public function test_rejected_supabase_response_is_not_treated_as_success(): void
    {
        $this->mockSignup(function (): array {
            return [
                'outcome' => 'rejected',
                'auth_id' => null,
                'error_code' => 'signup_rejected',
            ];
        });

        $result = $this->register();

        $this->assertSame(
            'needs_reconciliation',
            $result['status']
        );

        $this->assertSame(
            'signup_rejected',
            $result['error_code']
        );

        $this->assertSame(
            0,
            DB::table('signup_code_redemptions')->count()
        );

        $this->assertSame(
            0,
            DB::table('signup_codes')->value('use_count')
        );
    }

    public function test_missing_hook_claim_prevents_finalisation(): void
    {
        $this->mockSignup(function (): array {
            $this->createAuthAndAccount();

            return [
                'outcome' => 'response_received',
                'auth_id' => self::AUTH_ID,
                'error_code' => null,
            ];
        });

        $result = $this->register();

        $this->assertSame(
            'needs_reconciliation',
            $result['status']
        );

        $this->assertSame(
            'auth_claim_unverified',
            $result['error_code']
        );

        $this->assertSame(
            0,
            DB::table('signup_code_redemptions')->count()
        );

        $this->assertDatabaseHas('accounts_users', [
            'auth_id' => self::AUTH_ID,
            'first_name' => null,
            'last_name' => null,
        ]);
    }

    public function test_mismatched_hook_claim_prevents_finalisation(): void
    {
        $this->mockSignup(function (): array {
            $this->claimAuthId(self::OTHER_AUTH_ID);
            $this->createAuthAndAccount();

            return [
                'outcome' => 'response_received',
                'auth_id' => self::AUTH_ID,
                'error_code' => null,
            ];
        });

        $result = $this->register();

        $this->assertSame(
            'needs_reconciliation',
            $result['status']
        );

        $this->assertSame(
            'auth_claim_unverified',
            $result['error_code']
        );

        $this->assertSame(
            0,
            DB::table('signup_code_redemptions')->count()
        );
    }

    public function test_missing_provisioned_account_prevents_completion(): void
    {
        $this->mockSignup(function (): array {
            $this->claimAuthId();

            $this->createAuthAndAccount(
                createAccount: false
            );

            return [
                'outcome' => 'response_received',
                'auth_id' => self::AUTH_ID,
                'error_code' => null,
            ];
        });

        $result = $this->register();

        $this->assertSame(
            'needs_reconciliation',
            $result['status']
        );

        $this->assertSame(
            'account_not_provisioned',
            $result['error_code']
        );

        $this->assertDatabaseHas('signup_code_reservations', [
            'status' => 'reserved',
        ]);

        $this->assertSame(
            0,
            DB::table('signup_code_redemptions')->count()
        );
    }

    public function test_mismatched_auth_email_prevents_completion(): void
    {
        $this->mockSignup(function (): array {
            $this->claimAuthId();

            $this->createAuthAndAccount(
                email: 'different@example.com'
            );

            return [
                'outcome' => 'response_received',
                'auth_id' => self::AUTH_ID,
                'error_code' => null,
            ];
        });

        $result = $this->register();

        $this->assertSame(
            'needs_reconciliation',
            $result['status']
        );

        $this->assertSame(
            'account_not_provisioned',
            $result['error_code']
        );

        $this->assertSame(
            0,
            DB::table('signup_code_redemptions')->count()
        );
    }

    public function test_finalisation_failure_rolls_back_identity_update(): void
    {
        $this->mockSignup(function (): array {
            $this->claimAuthId();
            $this->createAuthAndAccount();

            return [
                'outcome' => 'response_received',
                'auth_id' => self::AUTH_ID,
                'error_code' => null,
            ];
        });

        /*
         * Force the invitation redemption insert to fail.
         * The preceding first/last-name update must
         * roll back in the same database transaction.
         */
        DB::statement(<<<'SQL'
CREATE TRIGGER reject_test_redemption
BEFORE INSERT ON signup_code_redemptions
BEGIN
    SELECT RAISE(ABORT, 'Simulated redemption failure');
END
SQL);

        $result = $this->register();

        $this->assertSame(
            'needs_reconciliation',
            $result['status']
        );

        $this->assertSame(
            'registration_finalisation_failed',
            $result['error_code']
        );

        $this->assertDatabaseHas('accounts_users', [
            'auth_id' => self::AUTH_ID,
            'first_name' => null,
            'last_name' => null,
        ]);

        $this->assertSame(
            0,
            DB::table('signup_code_redemptions')->count()
        );

        $this->assertSame(
            0,
            DB::table('signup_codes')->value('use_count')
        );

        $this->assertDatabaseHas('signup_code_reservations', [
            'status' => 'reserved',
        ]);
    }
}
