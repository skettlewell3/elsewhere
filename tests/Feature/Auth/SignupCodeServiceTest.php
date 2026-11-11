<?php

namespace Tests\Feature\Auth;

use App\Services\Auth\SignupCodeService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SignupCodeServiceTest extends TestCase
{
    private SignupCodeService $signupCodes;

    protected function setUp(): void
    {
        parent::setUp();

        $this->signupCodes = app(SignupCodeService::class);

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

        Schema::create('signup_code_reservations', function (Blueprint $table) {
            $table->id('reservation_id');

            $table->foreignId('signup_code_id')
                ->constrained('signup_codes', 'signup_code_id')
                ->restrictOnDelete();

            $table->string('token_hash', 64)->unique();
            $table->string('email', 255);
            $table->string('status', 16)->default('reserved');
            $table->timestampTz('expires_at');
            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('released_at')->nullable();
        });

        Schema::create('signup_code_redemptions', function (Blueprint $table) {
            $table->id('redemption_id');

            $table->foreignId('signup_code_id')
                ->constrained('signup_codes', 'signup_code_id')
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
        });

        Schema::create('registration_attempts', function (Blueprint $table) {
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

            $table->string('status', 32)
                ->default('reserved');

            $table->timestampTz('auth_requested_at')->nullable();
            $table->timestampTz('auth_created_at')->nullable();
            $table->timestampTz('provisioned_at')->nullable();
            $table->timestampTz('completed_at')->nullable();
            $table->timestampTz('failed_at')->nullable();

            $table->string('last_error_code', 100)->nullable();

            $table->timestampsTz();

            $table->index(
                ['status', 'updated_at'],
                'registration_attempts_status_idx'
            );

            $table->index(
                ['email', 'status'],
                'registration_attempts_email_idx'
            );
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('registration_attempts');
        Schema::dropIfExists('signup_code_redemptions');
        Schema::dropIfExists('signup_code_reservations');
        Schema::dropIfExists('signup_codes');

        parent::tearDown();
    }

    private function createCode(
        string $code = 'TEST-CODE',
        bool $active = true,
        ?int $maxUses = null,
        int $useCount = 0,
        ?string $expiresAt = null
    ): int {
        return DB::table('signup_codes')->insertGetId([
            'code' => $code,
            'active' => $active,
            'max_uses' => $maxUses,
            'use_count' => $useCount,
            'expires_at' => $expiresAt,
            'created_at' => now(),
        ]);
    }

    private function createAttempt(
        string $token,
        string $email,
        string $status = 'reserved',
        ?string $authId = null,
        bool $authConfirmed = false
    ): int {
        $reservationId = DB::table('signup_code_reservations')
            ->where('token_hash', hash('sha256', $token))
            ->value('reservation_id');

        $this->assertNotNull($reservationId);

        return DB::table('registration_attempts')
            ->insertGetId([
                'reservation_id' => $reservationId,
                'auth_id' => $authId,
                'email' => strtolower(trim($email)),
                'status' => $status,
                'auth_requested_at' =>
                    $status === 'reserved' ? null : now(),
                'auth_created_at' =>
                    $authConfirmed ? now() : null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
    }

    private function createConfirmedAttempt(
        string $token,
        string $authId,
        string $email
    ): int {
        return $this->createAttempt(
            token: $token,
            email: $email,
            status: 'auth_created',
            authId: $authId,
            authConfirmed: true
        );
    }

    public function test_valid_signup_code_is_accepted(): void
    {
        $this->createCode();

        $this->assertTrue(
            $this->signupCodes->isValid('TEST-CODE')
        );

        $this->assertTrue(
            $this->signupCodes->isValid(' TEST-CODE ')
        );
    }

    public function test_invalid_or_inactive_codes_are_rejected(): void
    {
        $this->createCode(
            code: 'INACTIVE',
            active: false
        );

        $this->assertFalse(
            $this->signupCodes->isValid('INVALID')
        );

        $this->assertFalse(
            $this->signupCodes->isValid('INACTIVE')
        );

        $this->assertFalse(
            $this->signupCodes->isValid('')
        );
    }

    public function test_expired_and_exhausted_codes_are_rejected(): void
    {
        $this->createCode(
            code: 'EXPIRED',
            expiresAt: now()->subDay()->toDateTimeString()
        );

        $this->createCode(
            code: 'EXHAUSTED',
            maxUses: 1,
            useCount: 1
        );

        $this->assertFalse(
            $this->signupCodes->isValid('EXPIRED')
        );

        $this->assertFalse(
            $this->signupCodes->isValid('EXHAUSTED')
        );

        $this->assertNull(
            $this->signupCodes->reserve(
                'EXPIRED',
                'user@example.com'
            )
        );

        $this->assertNull(
            $this->signupCodes->reserve(
                'EXHAUSTED',
                'user@example.com'
            )
        );
    }

    public function test_reservation_is_created_with_normalised_email(): void
    {
        $codeId = $this->createCode(maxUses: 2);

        $token = $this->signupCodes->reserve(
            ' TEST-CODE ',
            ' USER@EXAMPLE.COM '
        );

        $this->assertIsString($token);
        $this->assertSame(64, strlen($token));

        $this->assertDatabaseHas(
            'signup_code_reservations',
            [
                'signup_code_id' => $codeId,
                'token_hash' => hash('sha256', $token),
                'email' => 'user@example.com',
                'status' => 'reserved',
            ]
        );

        $this->assertDatabaseHas(
            'signup_codes',
            [
                'signup_code_id' => $codeId,
                'use_count' => 0,
            ]
        );
    }

    public function test_reservations_cannot_exceed_capacity(): void
    {
        $this->createCode(maxUses: 1);

        $firstToken = $this->signupCodes->reserve(
            'TEST-CODE',
            'first@example.com'
        );

        $secondToken = $this->signupCodes->reserve(
            'TEST-CODE',
            'second@example.com'
        );

        $this->assertNotNull($firstToken);
        $this->assertNull($secondToken);

        $this->assertSame(
            1,
            DB::table('signup_code_reservations')->count()
        );
    }

    public function test_releasing_a_reservation_restores_capacity(): void
    {
        $this->createCode(maxUses: 1);

        $token = $this->signupCodes->reserve(
            'TEST-CODE',
            'first@example.com'
        );

        $this->assertNotNull($token);

        $this->assertTrue(
            $this->signupCodes->release($token)
        );

        $this->assertFalse(
            $this->signupCodes->release($token)
        );

        $this->assertDatabaseHas(
            'signup_code_reservations',
            [
                'token_hash' => hash('sha256', $token),
                'status' => 'released',
            ]
        );

        $newToken = $this->signupCodes->reserve(
            'TEST-CODE',
            'second@example.com'
        );

        $this->assertNotNull($newToken);
    }

    public function test_expired_reservations_no_longer_occupy_capacity(): void
    {
        $this->createCode(maxUses: 1);

        $firstToken = $this->signupCodes->reserve(
            'TEST-CODE',
            'first@example.com'
        );

        $this->assertNotNull($firstToken);

        $this->travel(11)->minutes();

        $secondToken = $this->signupCodes->reserve(
            'TEST-CODE',
            'second@example.com'
        );

        $this->assertNotNull($secondToken);

        $this->assertSame(
            2,
            DB::table('signup_code_reservations')->count()
        );

        $this->assertDatabaseHas(
            'signup_code_reservations',
            [
                'token_hash' => hash('sha256', $firstToken),
                'status' => 'reserved',
            ]
        );
    }

    public function test_successful_redemption_updates_all_three_tables(): void
    {
        $codeId = $this->createCode(maxUses: 1);

        $token = $this->signupCodes->reserve(
            'TEST-CODE',
            'user@example.com'
        );

        $this->assertNotNull($token);

        $authId = '11111111-1111-4111-8111-111111111111';

        $this->createConfirmedAttempt(
            $token,
            $authId,
            'user@example.com'
        );

        $redeemed = $this->signupCodes->redeem(
            $token,
            $authId,
            'user@example.com'
        );

        $this->assertTrue($redeemed);

        $this->assertDatabaseHas(
            'signup_codes',
            [
                'signup_code_id' => $codeId,
                'use_count' => 1,
            ]
        );

        $this->assertDatabaseHas(
            'signup_code_reservations',
            [
                'token_hash' => hash('sha256', $token),
                'status' => 'redeemed',
            ]
        );

        $this->assertDatabaseHas(
            'signup_code_redemptions',
            [
                'signup_code_id' => $codeId,
                'auth_id' => $authId,
            ]
        );

        $this->assertNull(
            $this->signupCodes->reserve(
                'TEST-CODE',
                'another@example.com'
            )
        );
    }

    public function test_redeeming_same_reservation_twice_is_idempotent(): void
    {
        $this->createCode(maxUses: 2);

        $token = $this->signupCodes->reserve(
            'TEST-CODE',
            'user@example.com'
        );

        $this->assertNotNull($token);

        $authId = '22222222-2222-4222-8222-222222222222';

        $this->createConfirmedAttempt(
            $token,
            $authId,
            'user@example.com'
        );

        $this->assertTrue(
            $this->signupCodes->redeem(
                $token,
                $authId,
                'user@example.com'
            )
        );

        $this->assertTrue(
            $this->signupCodes->redeem(
                $token,
                $authId,
                'user@example.com'
            )
        );

        $this->assertSame(
            1,
            DB::table('signup_code_redemptions')->count()
        );

        $this->assertSame(
            1,
            DB::table('signup_codes')->value('use_count')
        );

        $reservationId = DB::table('signup_code_reservations')
            ->where('token_hash', hash('sha256', $token))
            ->value('reservation_id');

        $this->assertDatabaseHas('signup_code_redemptions', [
            'reservation_id' => $reservationId,
            'auth_id' => $authId,
        ]);
    }

    public function test_redemption_rejects_wrong_email_and_invalid_uuid(): void
    {
        $this->createCode(maxUses: 1);

        $token = $this->signupCodes->reserve(
            'TEST-CODE',
            'user@example.com'
        );

        $authId = '33333333-3333-4333-8333-333333333333';

        $this->assertFalse(
            $this->signupCodes->redeem(
                $token,
                $authId,
                'wrong@example.com'
            )
        );

        $this->assertFalse(
            $this->signupCodes->redeem(
                $token,
                'invalid-uuid',
                'user@example.com'
            )
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

    public function test_expired_reservation_cannot_be_redeemed(): void
    {
        $this->createCode(maxUses: 1);

        $token = $this->signupCodes->reserve(
            'TEST-CODE',
            'user@example.com'
        );

        $this->travel(11)->minutes();

        $this->assertFalse(
            $this->signupCodes->redeem(
                $token,
                '44444444-4444-4444-8444-444444444444',
                'user@example.com'
            )
        );

        $this->assertSame(
            0,
            DB::table('signup_code_redemptions')->count()
        );
    }

    public function test_released_reservation_cannot_be_redeemed(): void
    {
        $this->createCode(maxUses: 1);

        $token = $this->signupCodes->reserve(
            'TEST-CODE',
            'user@example.com'
        );

        $this->assertTrue(
            $this->signupCodes->release($token)
        );

        $this->assertFalse(
            $this->signupCodes->redeem(
                $token,
                '55555555-5555-4555-8555-555555555555',
                'user@example.com'
            )
        );

        $this->assertSame(
            0,
            DB::table('signup_code_redemptions')->count()
        );
    }

    public function test_expired_reservation_remains_occupied_during_auth_creation(): void
    {
        $this->createCode(maxUses: 1);

        $token = $this->signupCodes->reserve(
            'TEST-CODE',
            'first@example.com'
        );

        $this->assertNotNull($token);

        $this->createAttempt(
            token: $token,
            email: 'first@example.com',
            status: 'creating_auth'
        );

        $this->travel(11)->minutes();

        $anotherToken = $this->signupCodes->reserve(
            'TEST-CODE',
            'second@example.com'
        );

        $this->assertNull($anotherToken);

        $this->assertSame(
            1,
            DB::table('signup_code_reservations')->count()
        );

        $this->assertSame(
            0,
            DB::table('signup_codes')->value('use_count')
        );
    }

    public function test_reconciliation_attempt_continues_occupying_capacity(): void
    {
        $this->createCode(maxUses: 1);

        $token = $this->signupCodes->reserve(
            'TEST-CODE',
            'first@example.com'
        );

        $this->assertNotNull($token);

        $authId = '66666666-6666-4666-8666-666666666666';

        $this->createAttempt(
            token: $token,
            email: 'first@example.com',
            status: 'needs_reconciliation',
            authId: $authId
        );

        $this->travel(11)->minutes();

        $anotherToken = $this->signupCodes->reserve(
            'TEST-CODE',
            'second@example.com'
        );

        $this->assertNull($anotherToken);

        $this->assertFalse(
            $this->signupCodes->release($token)
        );
    }

    public function test_reservation_cannot_be_released_during_auth_creation(): void
    {
        $this->createCode(maxUses: 1);

        $token = $this->signupCodes->reserve(
            'TEST-CODE',
            'user@example.com'
        );

        $this->assertNotNull($token);

        $this->createAttempt(
            token: $token,
            email: 'user@example.com',
            status: 'creating_auth'
        );

        $this->assertFalse(
            $this->signupCodes->release($token)
        );

        $this->assertDatabaseHas(
            'signup_code_reservations',
            [
                'token_hash' => hash('sha256', $token),
                'status' => 'reserved',
                'released_at' => null,
            ]
        );
    }

    public function test_confirmed_auth_can_redeem_after_reservation_expiry(): void
    {
        $this->createCode(maxUses: 1);

        $token = $this->signupCodes->reserve(
            'TEST-CODE',
            'user@example.com'
        );

        $this->assertNotNull($token);

        $authId = '77777777-7777-4777-8777-777777777777';

        $this->createConfirmedAttempt(
            $token,
            $authId,
            'user@example.com'
        );

        $this->travel(11)->minutes();

        $this->assertTrue(
            $this->signupCodes->redeem(
                $token,
                $authId,
                'user@example.com'
            )
        );

        $this->assertDatabaseHas('signup_codes', [
            'code' => 'TEST-CODE',
            'use_count' => 1,
        ]);

        $this->assertDatabaseHas('signup_code_reservations', [
            'token_hash' => hash('sha256', $token),
            'status' => 'redeemed',
        ]);

        $this->assertDatabaseHas('signup_code_redemptions', [
            'auth_id' => $authId,
        ]);
    }

    public function test_unconfirmed_auth_cannot_redeem_reservation(): void
    {
        $this->createCode(maxUses: 1);

        $token = $this->signupCodes->reserve(
            'TEST-CODE',
            'user@example.com'
        );

        $this->assertNotNull($token);

        $authId = '88888888-8888-4888-8888-888888888888';

        $this->createAttempt(
            token: $token,
            email: 'user@example.com',
            status: 'creating_auth',
            authId: $authId,
            authConfirmed: false
        );

        $this->assertFalse(
            $this->signupCodes->redeem(
                $token,
                $authId,
                'user@example.com'
            )
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

    public function test_completed_redemption_can_be_retried_after_expiry(): void
    {
        $this->createCode(maxUses: 1);

        $token = $this->signupCodes->reserve(
            'TEST-CODE',
            'user@example.com'
        );

        $this->assertNotNull($token);

        $authId = '99999999-9999-4999-8999-999999999999';

        $this->createConfirmedAttempt(
            $token,
            $authId,
            'user@example.com'
        );

        $this->assertTrue(
            $this->signupCodes->redeem(
                $token,
                $authId,
                'user@example.com'
            )
        );

        $this->travel(11)->minutes();

        $this->assertTrue(
            $this->signupCodes->redeem(
                $token,
                $authId,
                'user@example.com'
            )
        );

        $this->assertSame(
            1,
            DB::table('signup_code_redemptions')->count()
        );

        $this->assertSame(
            1,
            DB::table('signup_codes')->value('use_count')
        );
    }

    public function test_reservation_cannot_be_redeemed_by_different_auth_identity(): void
    {
        $this->createCode(maxUses: 2);

        $token = $this->signupCodes->reserve(
            'TEST-CODE',
            'user@example.com'
        );

        $this->assertNotNull($token);

        $originalAuthId = 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';
        $differentAuthId = 'bbbbbbbb-bbbb-4bbb-8bbb-bbbbbbbbbbbb';

        $this->createConfirmedAttempt(
            $token,
            $originalAuthId,
            'user@example.com'
        );

        $this->assertTrue(
            $this->signupCodes->redeem(
                $token,
                $originalAuthId,
                'user@example.com'
            )
        );

        $this->assertFalse(
            $this->signupCodes->redeem(
                $token,
                $differentAuthId,
                'user@example.com'
            )
        );

        $this->assertSame(
            1,
            DB::table('signup_code_redemptions')->count()
        );

        $this->assertSame(
            1,
            DB::table('signup_codes')->value('use_count')
        );
    }

    /*
     |--------------------------------------------------------------------------
     | Attempt-based redemption tests
     |--------------------------------------------------------------------------
     */

    public function test_confirmed_attempt_can_redeem_without_token(): void
    {
        $codeId = $this->createCode(maxUses: 1);

        $token = $this->signupCodes->reserve(
            'TEST-CODE',
            'user@example.com'
        );

        $this->assertNotNull($token);

        $authId = '10101010-1010-4010-8010-101010101010';

        $attemptId = $this->createConfirmedAttempt(
            $token,
            $authId,
            'user@example.com'
        );

        $this->assertTrue(
            $this->signupCodes->redeemByAttempt($attemptId)
        );

        $reservationId = DB::table('registration_attempts')
            ->where('registration_attempt_id', $attemptId)
            ->value('reservation_id');

        $this->assertDatabaseHas('signup_code_redemptions', [
            'signup_code_id' => $codeId,
            'reservation_id' => $reservationId,
            'auth_id' => $authId,
        ]);

        $this->assertDatabaseHas('signup_code_reservations', [
            'reservation_id' => $reservationId,
            'status' => 'redeemed',
        ]);

        $this->assertSame(
            1,
            DB::table('signup_codes')->value('use_count')
        );
    }

    public function test_confirmed_attempt_can_redeem_after_expiry(): void
    {
        $this->createCode(maxUses: 1);

        $token = $this->signupCodes->reserve(
            'TEST-CODE',
            'user@example.com'
        );

        $this->assertNotNull($token);

        $authId = '20202020-2020-4020-8020-202020202020';

        $attemptId = $this->createConfirmedAttempt(
            $token,
            $authId,
            'user@example.com'
        );

        $this->travel(11)->minutes();

        $this->assertTrue(
            $this->signupCodes->redeemByAttempt($attemptId)
        );

        $this->assertSame(
            1,
            DB::table('signup_codes')->value('use_count')
        );

        $this->assertSame(
            1,
            DB::table('signup_code_redemptions')->count()
        );
    }

    public function test_attempt_redemption_is_idempotent(): void
    {
        $this->createCode(maxUses: 1);

        $token = $this->signupCodes->reserve(
            'TEST-CODE',
            'user@example.com'
        );

        $this->assertNotNull($token);

        $attemptId = $this->createConfirmedAttempt(
            $token,
            '30303030-3030-4030-8030-303030303030',
            'user@example.com'
        );

        $this->assertTrue(
            $this->signupCodes->redeemByAttempt($attemptId)
        );

        $this->assertTrue(
            $this->signupCodes->redeemByAttempt($attemptId)
        );

        $this->travel(11)->minutes();

        $this->assertTrue(
            $this->signupCodes->redeemByAttempt($attemptId)
        );

        $this->assertSame(
            1,
            DB::table('signup_code_redemptions')->count()
        );

        $this->assertSame(
            1,
            DB::table('signup_codes')->value('use_count')
        );
    }

    public function test_attempt_redemption_rejects_missing_and_invalid_attempts(): void
    {
        $this->createCode(maxUses: 1);

        $this->assertFalse(
            $this->signupCodes->redeemByAttempt(0)
        );

        $this->assertFalse(
            $this->signupCodes->redeemByAttempt(-1)
        );

        $this->assertFalse(
            $this->signupCodes->redeemByAttempt(999999)
        );

        $token = $this->signupCodes->reserve(
            'TEST-CODE',
            'user@example.com'
        );

        $this->assertNotNull($token);

        $attemptId = $this->createAttempt(
            token: $token,
            email: 'user@example.com',
            status: 'reserved'
        );

        $this->assertFalse(
            $this->signupCodes->redeemByAttempt($attemptId)
        );

        $this->assertSame(
            0,
            DB::table('signup_codes')->value('use_count')
        );
    }

    public function test_attempt_redemption_rejects_unconfirmed_auth(): void
    {
        $this->createCode(maxUses: 1);

        $token = $this->signupCodes->reserve(
            'TEST-CODE',
            'user@example.com'
        );

        $this->assertNotNull($token);

        $attemptId = $this->createAttempt(
            token: $token,
            email: 'user@example.com',
            status: 'creating_auth',
            authId: '40404040-4040-4040-8040-404040404040',
            authConfirmed: false
        );

        $this->assertFalse(
            $this->signupCodes->redeemByAttempt($attemptId)
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

    public function test_attempt_redemption_rejects_released_reservation(): void
    {
        $this->createCode(maxUses: 1);

        $token = $this->signupCodes->reserve(
            'TEST-CODE',
            'user@example.com'
        );

        $this->assertNotNull($token);

        $this->assertTrue(
            $this->signupCodes->release($token)
        );

        // Construct a confirmed attempt against the
        // released reservation to verify that the
        // reservation status is still enforced.

        $attemptId = $this->createConfirmedAttempt(
            $token,
            '50505050-5050-4050-8050-505050505050',
            'user@example.com'
        );

        $this->assertFalse(
            $this->signupCodes->redeemByAttempt($attemptId)
        );

        $this->assertSame(
            0,
            DB::table('signup_code_redemptions')->count()
        );
    }

    public function test_attempt_redemption_rejects_email_mismatch(): void
    {
        $this->createCode(maxUses: 1);

        $token = $this->signupCodes->reserve(
            'TEST-CODE',
            'user@example.com'
        );

        $this->assertNotNull($token);

        $attemptId = $this->createAttempt(
            token: $token,
            email: 'different@example.com',
            status: 'auth_created',
            authId: '60606060-6060-4060-8060-606060606060',
            authConfirmed: true
        );

        $this->assertFalse(
            $this->signupCodes->redeemByAttempt($attemptId)
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

    public function test_attempt_redemption_cannot_reuse_auth_identity(): void
    {
        $codeId = $this->createCode(maxUses: 2);

        $firstToken = $this->signupCodes->reserve(
            'TEST-CODE',
            'first@example.com'
        );

        $secondToken = $this->signupCodes->reserve(
            'TEST-CODE',
            'second@example.com'
        );

        $this->assertNotNull($firstToken);
        $this->assertNotNull($secondToken);

        $authId = '70707070-7070-4070-8070-707070707070';

        $firstReservationId = DB::table(
            'signup_code_reservations'
        )
            ->where(
                'token_hash',
                hash('sha256', $firstToken)
            )
            ->value('reservation_id');

        // Simulate a permanent redemption already
        // belonging to this Auth identity.

        DB::table('signup_code_redemptions')->insert([
            'signup_code_id' => $codeId,
            'reservation_id' => $firstReservationId,
            'auth_id' => $authId,
            'redeemed_at' => now(),
        ]);

        DB::table('signup_code_reservations')
            ->where('reservation_id', $firstReservationId)
            ->update(['status' => 'redeemed']);

        DB::table('signup_codes')
            ->where('signup_code_id', $codeId)
            ->increment('use_count');

        $secondAttemptId = $this->createConfirmedAttempt(
            $secondToken,
            $authId,
            'second@example.com'
        );

        $this->assertFalse(
            $this->signupCodes->redeemByAttempt($secondAttemptId)
        );

        $this->assertSame(
            1,
            DB::table('signup_code_redemptions')->count()
        );

        $this->assertSame(
            1,
            DB::table('signup_codes')->value('use_count')
        );

        $this->assertDatabaseHas(
            'signup_code_reservations',
            [
                'token_hash' => hash('sha256', $secondToken),
                'status' => 'reserved',
            ]
        );
    }
}
