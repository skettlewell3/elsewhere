<?php

namespace Tests\Feature\Auth;

use App\Services\Auth\RegistrationReconciliationService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RegistrationReconciliationServiceTest extends TestCase
{
    private const AUTH_ID =
        '11111111-1111-4111-8111-111111111111';

    private const ACCOUNT_ID =
        'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';

    private RegistrationReconciliationService $reconciliation;

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

        $this->createRegistrationTables();
        $this->createIdentityTables();

        $this->reconciliation = app(
            RegistrationReconciliationService::class
        );
    }

    protected function tearDown(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            Schema::dropIfExists('registration_attempts');
            Schema::dropIfExists('signup_code_redemptions');
            Schema::dropIfExists('signup_code_reservations');
            Schema::dropIfExists('signup_codes');

            DB::statement(
                'DROP TABLE IF EXISTS public.accounts_users'
            );

            DB::statement(
                'DROP TABLE IF EXISTS public.accounts'
            );

            DB::statement(
                'DROP TABLE IF EXISTS auth.users'
            );

            DB::statement('DETACH DATABASE public');
            DB::statement('DETACH DATABASE auth');
        }

        parent::tearDown();
    }

    private function createRegistrationTables(): void
    {
        Schema::create('signup_codes', function (Blueprint $table) {
            $table->id('signup_code_id');
            $table->string('code')->unique();
            $table->boolean('active')->default(true);
            $table->integer('max_uses')->nullable();
            $table->integer('use_count')->default(0);
            $table->timestampTz('expires_at')->nullable();
            $table->timestampsTz();
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
                $table->string('status', 16);
                $table->timestampTz('expires_at');
                $table->timestampTz('created_at');
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

                $table->uuid('auth_id')->nullable()->unique();
                $table->string('email', 255);
                $table->string('status', 32);

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

    private function createIdentityTables(): void
    {
        DB::statement(<<<'SQL'
CREATE TABLE auth.users (
    id TEXT PRIMARY KEY,
    email TEXT NOT NULL,
    raw_user_meta_data TEXT
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
    username TEXT NOT NULL,
    first_name TEXT,
    last_name TEXT,
    updated_at TEXT
)
SQL);
    }

    private function createAttempt(
        string $status = 'needs_reconciliation',
        ?string $authId = self::AUTH_ID,
        string $email = 'user@example.com'
    ): int {
        $codeId = DB::table('signup_codes')->insertGetId([
            'code' => 'TEST-CODE',
            'active' => true,
            'max_uses' => 1,
            'use_count' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $reservationId = DB::table(
            'signup_code_reservations'
        )->insertGetId([
            'signup_code_id' => $codeId,
            'token_hash' => hash(
                'sha256',
                'test-reservation-token'
            ),
            'email' => $email,
            'status' => 'reserved',
            'expires_at' => now()->addMinutes(10),
            'created_at' => now(),
        ]);

        return DB::table('registration_attempts')
            ->insertGetId([
                'reservation_id' => $reservationId,
                'auth_id' => $authId,
                'email' => $email,
                'status' => $status,
                'auth_requested_at' => now(),
                'auth_created_at' => null,
                'last_error_code' => 'connection_failure',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
    }

    private function createAuthUser(
        string $authId = self::AUTH_ID,
        string $email = 'user@example.com',
        ?array $metadata = [
            'first_name' => 'First',
            'last_name' => 'Last',
        ]
    ): void {
        DB::table('auth.users')->insert([
            'id' => $authId,
            'email' => $email,
            'raw_user_meta_data' => $metadata === null
                ? null
                : json_encode($metadata),
        ]);
    }

    private function createAccount(
        string $authId = self::AUTH_ID,
        string $accountType = 'user'
    ): void {
        DB::table('public.accounts')->insert([
            'account_id' => self::ACCOUNT_ID,
            'account_type' => $accountType,
        ]);

        DB::table('public.accounts_users')->insert([
            'auth_id' => $authId,
            'account_id' => self::ACCOUNT_ID,
            'username' => 'user_aaaaaaaa',
            'first_name' => null,
            'last_name' => null,
            'updated_at' => now(),
        ]);
    }

    private function getAttempt(int $attemptId): object
    {
        return DB::table('registration_attempts')
            ->where('registration_attempt_id', $attemptId)
            ->first();
    }

    private function assertInvitationUnused(): void
    {
        $this->assertSame(
            0,
            DB::table('signup_codes')->value('use_count')
        );

        $this->assertSame(
            0,
            DB::table('signup_code_redemptions')->count()
        );

        $this->assertDatabaseHas(
            'signup_code_reservations',
            ['status' => 'reserved']
        );
    }

    public function test_recovers_and_completes_existing_registration(): void
    {
        $attemptId = $this->createAttempt();

        $this->createAuthUser();
        $this->createAccount();

        $result = $this->reconciliation->reconcile($attemptId);

        $this->assertSame('completed', $result['status']);
        $this->assertNull($result['error_code']);

        $attempt = $this->getAttempt($attemptId);

        $this->assertSame('completed', $attempt->status);
        $this->assertSame(self::AUTH_ID, $attempt->auth_id);
        $this->assertNotNull($attempt->auth_created_at);
        $this->assertNotNull($attempt->provisioned_at);
        $this->assertNotNull($attempt->completed_at);
        $this->assertNull($attempt->last_error_code);

        $this->assertDatabaseHas(
            'public.accounts_users',
            [
                'auth_id' => self::AUTH_ID,
                'account_id' => self::ACCOUNT_ID,
                'first_name' => 'First',
                'last_name' => 'Last',
            ]
        );

        $this->assertDatabaseHas(
            'signup_code_reservations',
            [
                'reservation_id' => $attempt->reservation_id,
                'status' => 'redeemed',
            ]
        );

        $this->assertDatabaseHas(
            'signup_code_redemptions',
            [
                'reservation_id' => $attempt->reservation_id,
                'auth_id' => self::AUTH_ID,
            ]
        );

        $this->assertSame(
            1,
            DB::table('signup_codes')->value('use_count')
        );

        $this->assertSame(
            1,
            DB::table('auth.users')->count()
        );

        $this->assertSame(
            1,
            DB::table('public.accounts')->count()
        );

        $this->assertSame(
            1,
            DB::table('public.accounts_users')->count()
        );
    }

    public function test_recovered_registration_can_be_retried_safely(): void
    {
        $attemptId = $this->createAttempt();

        $this->createAuthUser();
        $this->createAccount();

        $firstResult = $this->reconciliation->reconcile(
            $attemptId
        );

        $secondResult = $this->reconciliation->reconcile(
            $attemptId
        );

        $this->assertSame(
            'completed',
            $firstResult['status']
        );

        $this->assertSame(
            'completed',
            $secondResult['status']
        );

        $this->assertSame(
            1,
            DB::table('signup_code_redemptions')->count()
        );

        $this->assertSame(
            1,
            DB::table('signup_codes')->value('use_count')
        );

        $this->assertSame(
            1,
            DB::table('auth.users')->count()
        );

        $this->assertSame(
            1,
            DB::table('public.accounts')->count()
        );
    }

    public function test_recovers_registration_after_reservation_expiry(): void
    {
        $attemptId = $this->createAttempt();

        $this->createAuthUser();
        $this->createAccount();

        $this->travel(11)->minutes();

        $result = $this->reconciliation->reconcile($attemptId);

        $this->assertSame('completed', $result['status']);

        $this->assertDatabaseHas(
            'registration_attempts',
            [
                'registration_attempt_id' => $attemptId,
                'status' => 'completed',
            ]
        );

        $this->assertSame(
            1,
            DB::table('signup_codes')->value('use_count')
        );
    }

    public function test_missing_registration_attempt_returns_not_found(): void
    {
        $result = $this->reconciliation->reconcile(999999);

        $this->assertSame('not_found', $result['status']);

        $this->assertSame(
            'attempt_not_found',
            $result['error_code']
        );
    }

    public function test_completed_registration_is_left_unchanged(): void
    {
        $attemptId = $this->createAttempt(
            status: 'completed'
        );

        $result = $this->reconciliation->reconcile($attemptId);

        $this->assertSame('completed', $result['status']);
        $this->assertNull($result['error_code']);

        $this->assertSame(
            'completed',
            $this->getAttempt($attemptId)->status
        );
    }

    public function test_non_reconciliation_status_is_rejected(): void
    {
        $attemptId = $this->createAttempt(
            status: 'creating_auth'
        );

        $this->createAuthUser();
        $this->createAccount();

        $result = $this->reconciliation->reconcile($attemptId);

        $this->assertSame('not_eligible', $result['status']);

        $this->assertSame(
            'invalid_attempt_status',
            $result['error_code']
        );

        $this->assertSame(
            'creating_auth',
            $this->getAttempt($attemptId)->status
        );

        $this->assertInvitationUnused();
    }

    public function test_missing_claimed_uuid_remains_unresolved(): void
    {
        $attemptId = $this->createAttempt(
            authId: null
        );

        $this->createAuthUser();
        $this->createAccount();

        $result = $this->reconciliation->reconcile($attemptId);

        $this->assertSame('unresolved', $result['status']);

        $this->assertSame(
            'auth_identity_not_claimed',
            $result['error_code']
        );

        $attempt = $this->getAttempt($attemptId);

        $this->assertSame(
            'needs_reconciliation',
            $attempt->status
        );

        $this->assertNull($attempt->auth_id);

        $this->assertInvitationUnused();
    }

    public function test_claimed_auth_user_not_found_remains_unresolved(): void
    {
        $attemptId = $this->createAttempt();

        $result = $this->reconciliation->reconcile($attemptId);

        $this->assertSame('unresolved', $result['status']);

        $this->assertSame(
            'auth_identity_not_found',
            $result['error_code']
        );

        $this->assertSame(
            'needs_reconciliation',
            $this->getAttempt($attemptId)->status
        );

        $this->assertInvitationUnused();
    }

    public function test_auth_email_mismatch_remains_unresolved(): void
    {
        $attemptId = $this->createAttempt();

        $this->createAuthUser(
            email: 'different@example.com'
        );

        $this->createAccount();

        $result = $this->reconciliation->reconcile($attemptId);

        $this->assertSame('unresolved', $result['status']);

        $this->assertSame(
            'auth_identity_not_found',
            $result['error_code']
        );

        $this->assertSame(
            'needs_reconciliation',
            $this->getAttempt($attemptId)->status
        );

        $this->assertInvitationUnused();
    }

    public function test_missing_elsewhere_account_remains_unresolved(): void
    {
        $attemptId = $this->createAttempt();

        $this->createAuthUser();

        $result = $this->reconciliation->reconcile($attemptId);

        $this->assertSame('unresolved', $result['status']);

        $this->assertSame(
            'account_not_provisioned',
            $result['error_code']
        );

        $this->assertSame(
            'needs_reconciliation',
            $this->getAttempt($attemptId)->status
        );

        $this->assertInvitationUnused();
    }

    public function test_non_user_account_type_remains_unresolved(): void
    {
        $attemptId = $this->createAttempt();

        $this->createAuthUser();

        $this->createAccount(
            accountType: 'business'
        );

        $result = $this->reconciliation->reconcile($attemptId);

        $this->assertSame('unresolved', $result['status']);

        $this->assertSame(
            'account_not_provisioned',
            $result['error_code']
        );

        $this->assertSame(
            'needs_reconciliation',
            $this->getAttempt($attemptId)->status
        );

        $this->assertInvitationUnused();
    }

    public function test_missing_identity_metadata_remains_unresolved(): void
    {
        $attemptId = $this->createAttempt();

        $this->createAuthUser(
            metadata: null
        );

        $this->createAccount();

        $result = $this->reconciliation->reconcile($attemptId);

        $this->assertSame('unresolved', $result['status']);

        $this->assertSame(
            'identity_metadata_missing',
            $result['error_code']
        );

        $this->assertSame(
            'needs_reconciliation',
            $this->getAttempt($attemptId)->status
        );

        $this->assertInvitationUnused();
    }

    public function test_invalid_identity_metadata_remains_unresolved(): void
    {
        $attemptId = $this->createAttempt();

        $this->createAuthUser(
            metadata: [
                'first_name' => '',
                'last_name' => 'Last',
            ]
        );

        $this->createAccount();

        $result = $this->reconciliation->reconcile($attemptId);

        $this->assertSame('unresolved', $result['status']);

        $this->assertSame(
            'identity_metadata_invalid',
            $result['error_code']
        );

        $this->assertSame(
            'needs_reconciliation',
            $this->getAttempt($attemptId)->status
        );

        $this->assertInvitationUnused();
    }

    public function test_failed_redemption_rolls_back_entire_finalisation(): void
    {
        $attemptId = $this->createAttempt();

        $this->createAuthUser();
        $this->createAccount();

        DB::statement(<<<'SQL'
CREATE TRIGGER reject_reconciliation_redemption
BEFORE INSERT ON signup_code_redemptions
BEGIN
    SELECT RAISE(ABORT, 'Simulated redemption failure');
END
SQL);

        $result = $this->reconciliation->reconcile($attemptId);

        $this->assertSame('unresolved', $result['status']);

        $this->assertSame(
            'finalisation_failed',
            $result['error_code']
        );

        $attempt = $this->getAttempt($attemptId);

        $this->assertSame(
            'needs_reconciliation',
            $attempt->status
        );

        $this->assertNull($attempt->auth_created_at);
        $this->assertNull($attempt->provisioned_at);
        $this->assertNull($attempt->completed_at);

        $this->assertDatabaseHas(
            'public.accounts_users',
            [
                'auth_id' => self::AUTH_ID,
                'first_name' => null,
                'last_name' => null,
            ]
        );

        $this->assertInvitationUnused();
    }
}
