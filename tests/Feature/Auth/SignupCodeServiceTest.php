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

            $table->uuid('auth_id')->unique();
            $table->timestampTz('redeemed_at')->useCurrent();
        });
    }

    protected function tearDown(): void
    {
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
        $codeId = $this->createCode(
            maxUses: 2
        );

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
        $this->createCode(
            maxUses: 1
        );

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
            DB::table('signup_code_reservations')
                ->count()
        );
    }

    public function test_releasing_a_reservation_restores_capacity(): void
    {
        $this->createCode(
            maxUses: 1
        );

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
        $this->createCode(
            maxUses: 1
        );

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
            DB::table('signup_code_reservations')
                ->count()
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
        $codeId = $this->createCode(
            maxUses: 1
        );

        $token = $this->signupCodes->reserve(
            'TEST-CODE',
            'user@example.com'
        );

        $this->assertNotNull($token);

        $authId = '11111111-1111-4111-8111-111111111111';

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

    public function test_same_reservation_cannot_be_redeemed_twice(): void
    {
        $this->createCode(
            maxUses: 2
        );

        $token = $this->signupCodes->reserve(
            'TEST-CODE',
            'user@example.com'
        );

        $authId = '22222222-2222-4222-8222-222222222222';

        $this->assertTrue(
            $this->signupCodes->redeem(
                $token,
                $authId,
                'user@example.com'
            )
        );

        $this->assertFalse(
            $this->signupCodes->redeem(
                $token,
                $authId,
                'user@example.com'
            )
        );

        $this->assertSame(
            1,
            DB::table('signup_code_redemptions')
                ->count()
        );

        $this->assertSame(
            1,
            DB::table('signup_codes')
                ->value('use_count')
        );
    }

    public function test_redemption_rejects_wrong_email_and_invalid_uuid(): void
    {
        $this->createCode(
            maxUses: 1
        );

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
            DB::table('signup_code_redemptions')
                ->count()
        );

        $this->assertSame(
            0,
            DB::table('signup_codes')
                ->value('use_count')
        );
    }

    public function test_expired_reservation_cannot_be_redeemed(): void
    {
        $this->createCode(
            maxUses: 1
        );

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
            DB::table('signup_code_redemptions')
                ->count()
        );
    }

    public function test_released_reservation_cannot_be_redeemed(): void
    {
        $this->createCode(
            maxUses: 1
        );

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
            DB::table('signup_code_redemptions')
                ->count()
        );
    }
}
