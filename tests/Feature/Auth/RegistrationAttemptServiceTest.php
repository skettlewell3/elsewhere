<?php

namespace Tests\Feature\Auth;

use App\Services\Auth\RegistrationAttemptService;
use App\Services\Auth\SignupCodeService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RegistrationAttemptServiceTest extends TestCase
{
    private RegistrationAttemptService $attempts;

    private SignupCodeService $signupCodes;

    protected function setUp(): void
    {
        parent::setUp();

        $this->attempts = app(RegistrationAttemptService::class);
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
                ->nullable()
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
            $table->string('status', 32)->default('reserved');

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

        DB::table('signup_codes')->insert([
            'code' => 'TEST-CODE',
            'active' => true,
            'max_uses' => 10,
            'use_count' => 0,
            'created_at' => now(),
        ]);
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('registration_attempts');
        Schema::dropIfExists('signup_code_redemptions');
        Schema::dropIfExists('signup_code_reservations');
        Schema::dropIfExists('signup_codes');

        parent::tearDown();
    }

    private function reserve(string $email = 'user@example.com'): string
    {
        $token = $this->signupCodes->reserve(
            'TEST-CODE',
            $email
        );

        $this->assertNotNull($token);

        return $token;
    }

    private function createAttempt(
        string $token,
        string $email = 'user@example.com'
    ): int {
        $attemptId = $this->attempts->create($token, $email);

        $this->assertNotNull($attemptId);

        return $attemptId;
    }

    private function createAuthAttempt(
        string $authId,
        string $email = 'user@example.com'
    ): array {
        $token = $this->reserve($email);
        $attemptId = $this->createAttempt($token, $email);

        $this->assertTrue(
            $this->attempts->beginAuthCreation($attemptId)
        );

        $this->assertTrue(
            $this->attempts->confirmAuthCreated(
                $attemptId,
                $authId,
                $email
            )
        );

        return [$token, $attemptId];
    }

    public function test_creates_attempt_from_valid_reservation(): void
    {
        $token = $this->reserve();

        $attemptId = $this->createAttempt(
            $token,
            ' USER@EXAMPLE.COM '
        );

        $attempt = $this->attempts->find($attemptId);

        $this->assertNotNull($attempt);
        $this->assertSame('reserved', $attempt->status);
        $this->assertSame('user@example.com', $attempt->email);
        $this->assertNull($attempt->auth_id);
        $this->assertNull($attempt->auth_requested_at);
    }

    public function test_rejects_invalid_or_mismatched_reservations(): void
    {
        $this->assertNull(
            $this->attempts->create(
                'invalid-token',
                'user@example.com'
            )
        );

        $token = $this->reserve();

        $this->assertNull(
            $this->attempts->create(
                $token,
                'wrong@example.com'
            )
        );

        $this->assertNull(
            $this->attempts->create(
                $token,
                ''
            )
        );

        $this->assertSame(
            0,
            DB::table('registration_attempts')->count()
        );
    }

    public function test_rejects_expired_reservation(): void
    {
        $token = $this->reserve();

        $this->travel(11)->minutes();

        $this->assertNull(
            $this->attempts->create(
                $token,
                'user@example.com'
            )
        );

        $this->assertSame(
            0,
            DB::table('registration_attempts')->count()
        );
    }

    public function test_rejects_released_reservation(): void
    {
        $token = $this->reserve();

        $this->assertTrue(
            $this->signupCodes->release($token)
        );

        $this->assertNull(
            $this->attempts->create(
                $token,
                'user@example.com'
            )
        );
    }

    public function test_same_reservation_cannot_create_multiple_attempts(): void
    {
        $token = $this->reserve();

        $firstId = $this->createAttempt($token);

        $secondId = $this->attempts->create(
            $token,
            'user@example.com'
        );

        $this->assertNotNull($firstId);
        $this->assertNull($secondId);

        $this->assertSame(
            1,
            DB::table('registration_attempts')->count()
        );
    }

    public function test_begins_auth_creation_only_once(): void
    {
        $token = $this->reserve();
        $attemptId = $this->createAttempt($token);

        $this->assertTrue(
            $this->attempts->beginAuthCreation($attemptId)
        );

        $attempt = $this->attempts->find($attemptId);

        $this->assertSame('creating_auth', $attempt->status);
        $this->assertNotNull($attempt->auth_requested_at);

        $this->assertFalse(
            $this->attempts->beginAuthCreation($attemptId)
        );
    }

    public function test_confirms_supabase_auth_identity(): void
    {
        $token = $this->reserve();
        $attemptId = $this->createAttempt($token);

        $authId = '11111111-1111-4111-8111-111111111111';

        $this->assertTrue(
            $this->attempts->beginAuthCreation($attemptId)
        );

        $this->assertTrue(
            $this->attempts->confirmAuthCreated(
                $attemptId,
                $authId,
                'user@example.com'
            )
        );

        $attempt = $this->attempts->find($attemptId);

        $this->assertSame('auth_created', $attempt->status);
        $this->assertSame($authId, $attempt->auth_id);
        $this->assertNotNull($attempt->auth_created_at);
    }

    public function test_cannot_confirm_auth_before_creation_begins(): void
    {
        $token = $this->reserve();
        $attemptId = $this->createAttempt($token);

        $this->assertFalse(
            $this->attempts->confirmAuthCreated(
                $attemptId,
                '22222222-2222-4222-8222-222222222222',
                'user@example.com'
            )
        );

        $this->assertSame(
            'reserved',
            $this->attempts->find($attemptId)->status
        );
    }

    public function test_rejects_mismatched_auth_identity_or_email(): void
    {
        $token = $this->reserve();
        $attemptId = $this->createAttempt($token);

        $authId = '33333333-3333-4333-8333-333333333333';

        $this->assertTrue(
            $this->attempts->beginAuthCreation($attemptId)
        );

        $this->assertFalse(
            $this->attempts->confirmAuthCreated(
                $attemptId,
                $authId,
                'wrong@example.com'
            )
        );

        $this->assertFalse(
            $this->attempts->confirmAuthCreated(
                $attemptId,
                'not-a-uuid',
                'user@example.com'
            )
        );

        // Simulate the Supabase hook claiming a proposed Auth UUID.

        DB::table('registration_attempts')
            ->where('registration_attempt_id', $attemptId)
            ->update([
                'auth_id' => $authId,
            ]);

        $this->assertFalse(
            $this->attempts->confirmAuthCreated(
                $attemptId,
                '44444444-4444-4444-8444-444444444444',
                'user@example.com'
            )
        );

        $this->assertTrue(
            $this->attempts->confirmAuthCreated(
                $attemptId,
                $authId,
                'user@example.com'
            )
        );
    }

    public function test_confirming_same_auth_identity_is_idempotent(): void
    {
        $authId = '55555555-5555-4555-8555-555555555555';

        [$token, $attemptId] = $this->createAuthAttempt($authId);

        $originalTimestamp = $this->attempts
            ->find($attemptId)
            ->auth_created_at;

        $this->assertTrue(
            $this->attempts->confirmAuthCreated(
                $attemptId,
                $authId,
                'user@example.com'
            )
        );

        $this->assertSame(
            $originalTimestamp,
            $this->attempts->find($attemptId)->auth_created_at
        );
    }

    public function test_provisioning_requires_confirmed_auth(): void
    {
        $token = $this->reserve();
        $attemptId = $this->createAttempt($token);

        $this->assertFalse(
            $this->attempts->beginProvisioning($attemptId)
        );

        $this->assertTrue(
            $this->attempts->beginAuthCreation($attemptId)
        );

        $this->assertFalse(
            $this->attempts->beginProvisioning($attemptId)
        );

        $authId = '66666666-6666-4666-8666-666666666666';

        $this->assertTrue(
            $this->attempts->confirmAuthCreated(
                $attemptId,
                $authId,
                'user@example.com'
            )
        );

        $this->assertTrue(
            $this->attempts->beginProvisioning($attemptId)
        );

        $this->assertSame(
            'provisioning',
            $this->attempts->find($attemptId)->status
        );

        $this->assertFalse(
            $this->attempts->beginProvisioning($attemptId)
        );
    }

    public function test_cannot_complete_without_redemption(): void
    {
        $authId = '77777777-7777-4777-8777-777777777777';

        [$token, $attemptId] = $this->createAuthAttempt($authId);

        $this->assertTrue(
            $this->attempts->beginProvisioning($attemptId)
        );

        $this->assertFalse(
            $this->attempts->complete($attemptId)
        );

        $this->assertSame(
            'provisioning',
            $this->attempts->find($attemptId)->status
        );
    }

    public function test_completes_registration_after_redemption(): void
    {
        $authId = '88888888-8888-4888-8888-888888888888';

        [$token, $attemptId] = $this->createAuthAttempt($authId);

        $this->assertTrue(
            $this->attempts->beginProvisioning($attemptId)
        );

        $this->assertTrue(
            $this->signupCodes->redeem(
                $token,
                $authId,
                'user@example.com'
            )
        );

        $this->assertTrue(
            $this->attempts->complete($attemptId)
        );

        $attempt = $this->attempts->find($attemptId);

        $this->assertSame('completed', $attempt->status);
        $this->assertNotNull($attempt->completed_at);
        $this->assertNotNull($attempt->provisioned_at);

        $this->assertDatabaseHas('signup_code_redemptions', [
            'auth_id' => $authId,
        ]);

        // Completion is idempotent.

        $this->assertTrue(
            $this->attempts->complete($attemptId)
        );

        $this->assertSame(
            1,
            DB::table('signup_codes')->value('use_count')
        );
    }

    public function test_uncertain_auth_result_requires_reconciliation(): void
    {
        $token = $this->reserve();
        $attemptId = $this->createAttempt($token);

        $this->assertTrue(
            $this->attempts->beginAuthCreation($attemptId)
        );

        $this->assertTrue(
            $this->attempts->needsReconciliation(
                $attemptId,
                'supabase_timeout'
            )
        );

        $attempt = $this->attempts->find($attemptId);

        $this->assertSame(
            'needs_reconciliation',
            $attempt->status
        );

        $this->assertSame(
            'supabase_timeout',
            $attempt->last_error_code
        );

        // The reservation cannot be released while unresolved.

        $this->assertFalse(
            $this->signupCodes->release($token)
        );

        // A verified Auth identity can resume the process.

        $authId = '99999999-9999-4999-8999-999999999999';

        $this->assertTrue(
            $this->attempts->confirmAuthCreated(
                $attemptId,
                $authId,
                'user@example.com'
            )
        );

        $this->assertSame(
            'auth_created',
            $this->attempts->find($attemptId)->status
        );
    }

    public function test_definite_failure_allowed_only_before_auth_creation(): void
    {
        $token = $this->reserve();
        $attemptId = $this->createAttempt($token);

        $this->assertTrue(
            $this->attempts->failBeforeAuthCreation(
                $attemptId,
                'registration_cancelled'
            )
        );

        $attempt = $this->attempts->find($attemptId);

        $this->assertSame('failed', $attempt->status);
        $this->assertNotNull($attempt->failed_at);

        $this->assertSame(
            'registration_cancelled',
            $attempt->last_error_code
        );

        $this->assertFalse(
            $this->attempts->beginAuthCreation($attemptId)
        );

        // The reservation may now be explicitly released.

        $this->assertTrue(
            $this->signupCodes->release($token)
        );

        $secondToken = $this->reserve('second@example.com');
        $secondAttemptId = $this->createAttempt(
            $secondToken,
            'second@example.com'
        );

        $this->assertTrue(
            $this->attempts->beginAuthCreation($secondAttemptId)
        );

        $this->assertFalse(
            $this->attempts->failBeforeAuthCreation(
                $secondAttemptId,
                'registration_cancelled'
            )
        );
    }

    public function test_missing_attempt_returns_null_and_rejects_transition(): void
    {
        $this->assertNull(
            $this->attempts->find(999999)
        );

        $this->assertFalse(
            $this->attempts->beginAuthCreation(999999)
        );

        $this->assertFalse(
            $this->attempts->beginProvisioning(999999)
        );

        $this->assertFalse(
            $this->attempts->complete(999999)
        );

        $this->assertFalse(
            $this->attempts->needsReconciliation(
                999999,
                'unknown_error'
            )
        );
    }
}
