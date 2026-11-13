<?php

namespace Tests\Feature;

use App\Models\AccountUser;
use App\Models\App;
use App\Models\AppMembership;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AppLaunchControllerTest extends TestCase
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
            \App\Http\Middleware\RefreshSupabaseSession::class
        );
    
        config()->set('session.driver', 'array');
    
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
            $table->string('image')->nullable();
            $table->string('alt')->nullable();
            $table->string('status')->default('coming_soon');
            $table->string('info_url')->nullable();
            $table->string('launch_url')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
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
            'launch_url' =>
                'https://perfect10.elsewhere.uk.com',
            'sort_order' => 1,
        ]);
    }

    private function signIn(): AccountUser
    {
        DB::table('accounts_users')->insert([
            'auth_id' => self::AUTH_ID,
            'account_id' =>
                'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa',
            'username' => 'test_player',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user = AccountUser::query()
            ->where('auth_id', self::AUTH_ID)
            ->firstOrFail();

        $this->actingAs($user, 'web');

        return $user;
    }

    private function createMembership(
        App $app,
        string $status = 'active'
    ): AppMembership {
        return AppMembership::query()->create([
            'auth_id' => self::AUTH_ID,
            'app_id' => $app->app_id,
            'status' => $status,
            'registered_at' =>
                $status === 'active' ? now() : null,
        ]);
    }

    private function launchUrl(): string
    {
        return route('apps.launch', [
            'app' => 'perfect10',
        ]);
    }

    public function test_guest_is_redirected_to_central_login(): void
    {
        $this->createApp();

        $response = $this->get($this->launchUrl());

        $response->assertRedirect(route('login'));
    }

    public function test_guest_launch_destination_is_preserved(): void
    {
        $this->createApp();

        $response = $this->get($this->launchUrl());

        $response->assertRedirect(route('login'));

        $response->assertSessionHas(
            'url.intended',
            $this->launchUrl()
        );
    }

    public function test_active_member_reaches_launch_gateway(): void
    {
        $app = $this->createApp();

        $this->signIn();
        $this->createMembership($app);

        $response = $this->get($this->launchUrl());

        $response->assertRedirect(route('discover'));

        $response->assertSessionHas(
            'app_launch_message',
            'Perfect10 is ready in your Elsewhere account. The app login connection is not available yet.'
        );

        $this->assertDatabaseHas('app_memberships', [
            'auth_id' => self::AUTH_ID,
            'app_id' => $app->app_id,
            'status' => 'active',
        ]);
    }

    public function test_unregistered_user_is_sent_towards_onboarding(): void
    {
        $app = $this->createApp();

        $this->signIn();

        $response = $this->get($this->launchUrl());

        $response->assertRedirect(route('discover'));

        $response->assertSessionHas(
            'app_launch_message',
            'You can create a Perfect10 profile once application registration is connected.'
        );

        $this->assertDatabaseCount('app_memberships', 0);
    }

    public function test_removed_membership_is_treated_as_unregistered(): void
    {
        $app = $this->createApp();

        $this->signIn();
        $this->createMembership($app, 'removed');

        $response = $this->get($this->launchUrl());

        $response->assertRedirect(route('discover'));

        $response->assertSessionHas(
            'app_launch_message',
            'You can create a Perfect10 profile once application registration is connected.'
        );

        $this->assertDatabaseHas('app_memberships', [
            'auth_id' => self::AUTH_ID,
            'status' => 'removed',
        ]);
    }

    public function test_pending_membership_cannot_launch(): void
    {
        $app = $this->createApp();

        $this->signIn();
        $this->createMembership($app, 'pending');

        $response = $this->get($this->launchUrl());

        $response->assertRedirect(route('discover'));

        $response->assertSessionHas(
            'app_launch_message',
            'Your Perfect10 registration is pending.'
        );

        $this->assertDatabaseHas('app_memberships', [
            'auth_id' => self::AUTH_ID,
            'status' => 'pending',
        ]);
    }

    public function test_suspended_membership_cannot_launch(): void
    {
        $app = $this->createApp();

        $this->signIn();
        $this->createMembership($app, 'suspended');

        $response = $this->get($this->launchUrl());

        $response->assertRedirect(route('discover'));

        $response->assertSessionHas(
            'app_launch_message',
            'Your Perfect10 access is currently suspended.'
        );

        $this->assertDatabaseHas('app_memberships', [
            'auth_id' => self::AUTH_ID,
            'status' => 'suspended',
        ]);
    }

    public function test_coming_soon_application_cannot_launch(): void
    {
        $this->createApp('coming_soon');

        $this->signIn();

        $response = $this->get($this->launchUrl());

        $response->assertRedirect(route('discover'));

        $response->assertSessionHas(
            'app_launch_message',
            'Perfect10 is not currently available.'
        );
    }

    public function test_maintenance_application_cannot_launch(): void
    {
        $this->createApp('maintenance');

        $this->signIn();

        $response = $this->get($this->launchUrl());

        $response->assertRedirect(route('discover'));

        $response->assertSessionHas(
            'app_launch_message',
            'Perfect10 is not currently available.'
        );
    }

    public function test_inactive_application_cannot_launch(): void
    {
        $this->createApp('inactive');

        $this->signIn();

        $response = $this->get($this->launchUrl());

        $response->assertRedirect(route('discover'));

        $response->assertSessionHas(
            'app_launch_message',
            'Perfect10 is not currently available.'
        );
    }

    public function test_unknown_application_returns_404(): void
    {
        $response = $this->get(
            route('apps.launch', [
                'app' => 'unknown-application',
            ])
        );

        $response->assertNotFound();
    }

    public function test_launching_does_not_create_memberships(): void
    {
        $this->createApp();

        $this->signIn();

        $this->get($this->launchUrl())
            ->assertRedirect(route('discover'));

        $this->get($this->launchUrl())
            ->assertRedirect(route('discover'));

        $this->assertDatabaseCount('app_memberships', 0);
    }
}
