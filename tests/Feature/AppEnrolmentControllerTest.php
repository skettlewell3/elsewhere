<?php

namespace Tests\Feature;

use App\Http\Middleware\RefreshSupabaseSession;
use App\Models\AccountUser;
use App\Models\App;
use App\Models\AppMembership;
use App\Services\Apps\AppEnrolmentService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class AppEnrolmentControllerTest extends TestCase
{
    private const AUTH_ID =
        '11111111-1111-4111-8111-111111111111';

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::getDriverName() !== 'sqlite') {
            $this->markTestSkipped(
                'These isolated tests require SQLite.'
            );
        }

        $this->withoutMiddleware(
            RefreshSupabaseSession::class
        );

        config()->set('session.driver', 'array');
        config()->set('app_enrolment.enabled', false);
        config()->set(
            'elsewhere_oauth.perfect10_url',
            'http://localhost:8001'
        );

        $this->createTestTables();
    }

    protected function tearDown(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            Schema::dropIfExists('app_memberships');
            Schema::dropIfExists('apps');
            Schema::dropIfExists('accounts_users');
        }

        parent::tearDown();
    }

    private function createTestTables(): void
    {
        Schema::create('accounts_users', function (Blueprint $table) {
            $table->uuid('auth_id')->primary();
            $table->uuid('account_id');
            $table->string('username');
            $table->timestamps();
        });

        Schema::create('apps', function (Blueprint $table) {
            $table->id('app_id');
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('category');
            $table->text('description')->nullable();
            $table->string('status')->default('coming_soon');
            $table->timestamps();
        });

        Schema::create('app_memberships', function (Blueprint $table) {
            $table->id('app_membership_id');

            $table->foreignUuid('auth_id')
                ->constrained('accounts_users', 'auth_id');

            $table->foreignId('app_id')
                ->constrained('apps', 'app_id');

            $table->string('status')->default('pending');
            $table->timestamp('registered_at')->nullable();
            $table->timestamps();

            $table->unique(['auth_id', 'app_id']);
        });
    }

    private function createApp(
        string $status = 'active'
    ): App {
        return App::query()->create([
            'slug' => 'perfect10',
            'name' => 'Perfect10',
            'category' => 'Sports',
            'description' => 'Football prediction game.',
            'status' => $status,
        ]);
    }

    private function signIn(): AccountUser
    {
        $user = AccountUser::query()->create([
            'auth_id' => self::AUTH_ID,
            'account_id' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa',
            'username' => 'test_player',
        ]);

        $this->actingAs($user, 'web');

        return $user;
    }

    private function createMembership(
        App $app,
        string $status
    ): AppMembership {
        return AppMembership::query()->create([
            'auth_id' => self::AUTH_ID,
            'app_id' => $app->app_id,
            'status' => $status,
            'registered_at' => $status === 'active'
                ? now()
                : null,
        ]);
    }

    private function enrolUrl(): string
    {
        return route('apps.enrol', [
            'app' => 'perfect10',
        ]);
    }

    private function submitUrl(): string
    {
        return route('apps.enrol.store', [
            'app' => 'perfect10',
        ]);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->createApp();

        $this->get($this->enrolUrl())
            ->assertRedirect(route('login'));
    }

    public function test_guest_enrolment_destination_is_preserved(): void
    {
        $this->createApp();

        $this->get($this->enrolUrl())
            ->assertRedirect(route('login'))
            ->assertSessionHas(
                'url.intended',
                $this->enrolUrl()
            );
    }

    public function test_new_user_can_view_confirmation_page(): void
    {
        $this->createApp();
        $this->signIn();

        $this->get($this->enrolUrl())
            ->assertOk()
            ->assertSee('Join Perfect10')
            ->assertSee('Create your app profile')
            ->assertSee('Registration coming soon');

        $this->assertDatabaseCount('app_memberships', 0);
    }

    public function test_pending_member_can_view_resume_page(): void
    {
        $app = $this->createApp();
        $this->signIn();
        $this->createMembership($app, 'pending');

        config()->set('app_enrolment.enabled', true);

        $this->get($this->enrolUrl())
            ->assertOk()
            ->assertSee('Complete your registration')
            ->assertSee('Continue registration');
    }

    public function test_active_member_is_redirected_to_launch(): void
    {
        $app = $this->createApp();
        $this->signIn();
        $this->createMembership($app, 'active');

        $this->get($this->enrolUrl())
            ->assertRedirect(
                route('apps.launch', $app)
            );
    }

    public function test_suspended_member_cannot_view_registration(): void
    {
        $app = $this->createApp();
        $this->signIn();
        $this->createMembership($app, 'suspended');

        $this->get($this->enrolUrl())
            ->assertRedirect(route('discover'));
    }

    public function test_removed_member_cannot_view_registration(): void
    {
        $app = $this->createApp();
        $this->signIn();
        $this->createMembership($app, 'removed');

        $this->get($this->enrolUrl())
            ->assertRedirect(route('discover'));
    }

    public function test_inactive_app_cannot_show_registration(): void
    {
        $this->createApp('maintenance');
        $this->signIn();

        $this->get($this->enrolUrl())
            ->assertRedirect(route('discover'));
    }

    public function test_disabled_enrolment_rejects_post(): void
    {
        $this->createApp();
        $this->signIn();

        $service = Mockery::mock(
            AppEnrolmentService::class
        );

        $service->shouldNotReceive('enrol');

        $this->app->instance(
            AppEnrolmentService::class,
            $service
        );

        $this->post($this->submitUrl())
            ->assertStatus(503);

        $this->assertDatabaseCount('app_memberships', 0);
    }

    public function test_guest_cannot_submit_enrolment(): void
    {
        $this->createApp();

        config()->set('app_enrolment.enabled', true);

        $service = Mockery::mock(
            AppEnrolmentService::class
        );

        $service->shouldNotReceive('enrol');

        $this->app->instance(
            AppEnrolmentService::class,
            $service
        );

        $this->post($this->submitUrl())
            ->assertRedirect(route('login'));

        $this->assertDatabaseCount('app_memberships', 0);
    }

    public function test_successful_enrolment_redirects_to_launch(): void
    {
        $app = $this->createApp();
        $user = $this->signIn();

        config()->set('app_enrolment.enabled', true);

        $service = Mockery::mock(
            AppEnrolmentService::class
        );

        $service->shouldReceive('enrol')
            ->once()
            ->withArgs(function (
                AccountUser $actualUser,
                App $actualApp
            ) use ($user, $app): bool {
                return $actualUser->auth_id === $user->auth_id
                    && $actualApp->app_id === $app->app_id;
            })
            ->andReturn(
                new AppMembership([
                    'auth_id' => self::AUTH_ID,
                    'app_id' => $app->app_id,
                    'status' => 'active',
                ])
            );

        $this->app->instance(
            AppEnrolmentService::class,
            $service
        );

        $this->post($this->submitUrl())
            ->assertRedirect(
                route('apps.launch', $app)
            );
    }

    public function test_provisioning_failure_returns_to_confirmation(): void
    {
        $app = $this->createApp();
        $user = $this->signIn();

        config()->set('app_enrolment.enabled', true);

        $service = Mockery::mock(
            AppEnrolmentService::class
        );

        $service->shouldReceive('enrol')
            ->once()
            ->withArgs(function (
                AccountUser $actualUser,
                App $actualApp
            ) use ($user, $app): bool {
                return $actualUser->auth_id === $user->auth_id
                    && $actualApp->app_id === $app->app_id;
            })
            ->andThrow(
                new RuntimeException('Provisioning unavailable')
            );

        $this->app->instance(
            AppEnrolmentService::class,
            $service
        );

        $this->post($this->submitUrl())
            ->assertRedirect(
                route('apps.enrol', $app)
            )
            ->assertSessionHas(
                'app_enrolment_error',
                'Registration could not be completed. Please try again later.'
            );
    }
}
