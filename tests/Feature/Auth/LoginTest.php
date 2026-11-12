<?php

namespace Tests\Feature\Auth;

use App\Exceptions\SupabaseLoginException;
use App\Models\AccountUser;
use App\Services\Auth\LoginRateLimiter;
use App\Services\Auth\SupabaseAuthService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class LoginTest extends TestCase
{
    private const AUTH_ID = '11111111-1111-4111-8111-111111111111';

    private const ACCOUNT_ID = '22222222-2222-4222-8222-222222222222';

    private const EMAIL = 'test@example.com';

    private const IP = '127.0.0.1';

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();

        config()->set('cache.default', 'array');
        config()->set('cache.limiter', 'array');
        config()->set('session.driver', 'array');

        if (
            config('database.default') !== 'sqlite' ||
            config('database.connections.sqlite.database') !== ':memory:'
        ) {
            throw new \RuntimeException(
                'Authentication tests require an in-memory SQLite database.'
            );
        }

        $this->createAccountTables();
    }

    private function createAccountTables(): void
    {
        Schema::connection('sqlite')->create(
            'accounts',
            function (Blueprint $table): void {
                $table->uuid('account_id')->primary();
                $table->string('account_type');
                $table->timestamps();
            }
        );

        Schema::connection('sqlite')->create(
            'accounts_users',
            function (Blueprint $table): void {
                $table->uuid('auth_id')->primary();
                $table->uuid('account_id')->unique();
                $table->string('username', 32)->unique();
                $table->string('first_name', 100)->nullable();
                $table->string('last_name', 100)->nullable();
                $table->boolean('is_founding_member')
                    ->default(false);
                $table->timestamps();
            }
        );
    }

    private function createAccountUser(): AccountUser
    {
        Schema::connection('sqlite')
            ->getConnection()
            ->table('accounts')
            ->insert([
                'account_id' => self::ACCOUNT_ID,
                'account_type' => 'user',
            ]);

        return AccountUser::query()->create([
            'auth_id' => self::AUTH_ID,
            'account_id' => self::ACCOUNT_ID,
            'username' => 'testuser',
            'first_name' => 'Test',
            'last_name' => 'User',
            'is_founding_member' => false,
        ]);
    }

    private function successfulAuthResponse(): array
    {
        return [
            'user' => [
                'id' => self::AUTH_ID,
            ],
            'access_token' => 'test-access-token',
            'refresh_token' => 'test-refresh-token',
            'expires_at' => now()->addHour()->timestamp,
        ];
    }

    private function loginData(
        string $email = self::EMAIL
    ): array {
        return [
            'email' => $email,
            'password' => 'test-password',
        ];
    }

    private function limiterRequest(
        string $email = self::EMAIL
    ): Request {
        return Request::create(
            '/login',
            'POST',
            ['email' => $email],
            [],
            [],
            ['REMOTE_ADDR' => self::IP]
        );
    }

    private function mockSuccessfulSignIn(): void
    {
        $mock = Mockery::mock(SupabaseAuthService::class);

        $mock->shouldReceive('signIn')
            ->once()
            ->with(self::EMAIL, 'test-password')
            ->andReturn($this->successfulAuthResponse());

        $this->app->instance(SupabaseAuthService::class, $mock);
    }

    public function test_valid_credentials_authenticate_account_user(): void
    {
        $accountUser = $this->createAccountUser();

        $this->mockSuccessfulSignIn();

        $response = $this->post(
            '/login',
            $this->loginData()
        );

        $response->assertRedirect('/');

        $this->assertAuthenticatedAs($accountUser, 'web');

        $response->assertSessionHas(
            'supabase_access_token',
            'test-access-token'
        );

        $response->assertSessionHas(
            'supabase_refresh_token',
            'test-refresh-token'
        );

        $response->assertSessionHas(
            'supabase_expires_at'
        );
    }

    public function test_invalid_credentials_return_login_error(): void
    {
        $mock = Mockery::mock(SupabaseAuthService::class);

        $mock->shouldReceive('signIn')
            ->once()
            ->andThrow(
                new SupabaseLoginException(
                    'invalid_credentials'
                )
            );

        $this->app->instance(SupabaseAuthService::class, $mock);

        $response = $this->from('/login')->post(
            '/login',
            $this->loginData()
        );

        $response->assertRedirect('/login');

        $response->assertSessionHasErrorsIn('login', [
            'email' => 'The email or password is incorrect.',
        ]);

        $response->assertSessionHasInput(
            'email',
            self::EMAIL
        );

        $this->assertGuest('web');
    }

    public function test_missing_credentials_are_rejected_before_supabase(): void
    {
        $mock = Mockery::mock(SupabaseAuthService::class);

        $mock->shouldNotReceive('signIn');

        $this->app->instance(SupabaseAuthService::class, $mock);

        $response = $this->from('/login')->post(
            '/login',
            [
                'email' => '',
                'password' => '',
            ]
        );

        $response->assertRedirect('/login');

        $response->assertSessionHasErrorsIn(
            'login',
            ['email', 'password']
        );

        $this->assertGuest('web');
    }

    public function test_sixth_failed_login_is_blocked_before_supabase(): void
    {
        $request = $this->limiterRequest();

        $limiter = app(LoginRateLimiter::class);

        for ($i = 0; $i < 5; $i++) {
            $limiter->recordFailure($request);
        }

        $mock = Mockery::mock(SupabaseAuthService::class);

        $mock->shouldNotReceive('signIn');

        $this->app->instance(SupabaseAuthService::class, $mock);

        $response = $this->from('/login')->post(
            '/login',
            $this->loginData()
        );

        $response->assertRedirect('/login');

        $response->assertSessionHasErrorsIn(
            'login',
            ['email']
        );

        $this->assertGuest('web');
    }

    public function test_ip_request_limit_blocks_login(): void
    {
        $request = $this->limiterRequest();

        $limiter = app(LoginRateLimiter::class);

        for ($i = 0; $i < 30; $i++) {
            $limiter->recordRequest($request);
        }

        $mock = Mockery::mock(SupabaseAuthService::class);

        $mock->shouldNotReceive('signIn');

        $this->app->instance(SupabaseAuthService::class, $mock);

        $response = $this->from('/login')->post(
            '/login',
            $this->loginData('another@example.com')
        );

        $response->assertRedirect('/login');

        $response->assertSessionHasErrorsIn(
            'login',
            ['email']
        );

        $this->assertGuest('web');
    }

    public function test_successful_login_clears_failure_counter(): void
    {
        $this->createAccountUser();

        $request = $this->limiterRequest();

        $limiter = app(LoginRateLimiter::class);

        for ($i = 0; $i < 3; $i++) {
            $limiter->recordFailure($request);
        }

        $this->mockSuccessfulSignIn();

        $response = $this->post(
            '/login',
            $this->loginData()
        );

        $response->assertRedirect('/');

        $this->assertAuthenticated('web');

        $this->assertSame(
            0,
            RateLimiter::attempts(
                $limiter->failureKey($request)
            )
        );
    }

    public function test_logout_clears_laravel_authentication(): void
    {
        $accountUser = $this->createAccountUser();

        $mock = Mockery::mock(SupabaseAuthService::class);

        $mock->shouldReceive('signOut')
            ->once()
            ->with('test-access-token');

        $this->app->instance(SupabaseAuthService::class, $mock);

        $response = $this
            ->actingAs($accountUser, 'web')
            ->withSession([
                'supabase_access_token' => 'test-access-token',
                'supabase_refresh_token' => 'test-refresh-token',
                'supabase_expires_at' => now()->addHour()->timestamp,
            ])
            ->post('/logout');

        $response->assertRedirect('/');

        $this->assertGuest('web');

        $response->assertSessionMissing(
            'supabase_access_token'
        );

        $response->assertSessionMissing(
            'supabase_refresh_token'
        );
    }
}
