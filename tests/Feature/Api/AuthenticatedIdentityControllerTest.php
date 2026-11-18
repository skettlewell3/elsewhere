<?php

namespace Tests\Feature\Api;

use App\Models\AccountUser;
use App\Models\App;
use App\Models\AppMembership;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Passport\Client;
use Laravel\Passport\Passport;
use Tests\TestCase;

class AuthenticatedIdentityControllerTest extends TestCase
{
    private const AUTH_ID =
        '11111111-1111-4111-8111-111111111111';

    private const CLIENT_ID =
        '22222222-2222-4222-8222-222222222222';

    private const OTHER_CLIENT_ID =
        '33333333-3333-4333-8333-333333333333';

    protected function setUp(): void
    {
        parent::setUp();

        if (DB::getDriverName() !== 'sqlite') {
            $this->markTestSkipped(
                'These isolated tests require SQLite.'
            );
        }

        config()->set(
            'ecosystem.perfect10_client_id',
            self::CLIENT_ID
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

    private function createUser(): AccountUser
    {
        DB::table('accounts_users')->insert([
            'auth_id' => self::AUTH_ID,
            'account_id' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa',
            'username' => 'test_player',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return AccountUser::query()
            ->where('auth_id', self::AUTH_ID)
            ->firstOrFail();
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
            'launch_url' => 'https://perfect10.elsewhere.uk.com',
            'sort_order' => 1,
        ]);
    }

    private function createMembership(
        App $app,
        string $status = 'active'
    ): AppMembership {
        return AppMembership::query()->create([
            'auth_id' => self::AUTH_ID,
            'app_id' => $app->app_id,
            'status' => $status,
            'registered_at' => $status === 'active' ? now() : null,
        ]);
    }

    private function authenticateApi(
        AccountUser $user,
        array $scopes = ['identity:read'],
        string $clientId = self::CLIENT_ID
    ): void {
        $client = new Client;

        $client->setAttribute('id', $clientId);

        Passport::actingAs(
            $user,
            $scopes,
            'api',
            $client
        );

        Auth::guard('api')->setClient($client);
    }

    private function identityUrl(): string
    {
        return route('api.auth.me');
    }

    public function test_guest_cannot_access_identity(): void
    {
        $this->getJson($this->identityUrl())
            ->assertUnauthorized()
            ->assertJson([
                'message' => 'Unauthenticated.',
            ]);
    }

    public function test_token_without_identity_scope_is_rejected(): void
    {
        $user = $this->createUser();
        $app = $this->createApp();

        $this->createMembership($app);

        $this->authenticateApi($user, []);

        $this->getJson($this->identityUrl())
            ->assertForbidden();
    }

    public function test_another_oauth_client_is_rejected(): void
    {
        $user = $this->createUser();
        $app = $this->createApp();

        $this->createMembership($app);

        $this->authenticateApi(
            $user,
            ['identity:read'],
            self::OTHER_CLIENT_ID
        );

        $this->getJson($this->identityUrl())
            ->assertForbidden()
            ->assertJson([
                'message' => 'Application access denied.',
            ]);
    }

    public function test_missing_client_configuration_is_rejected(): void
    {
        $user = $this->createUser();
        $app = $this->createApp();

        $this->createMembership($app);

        config()->set(
            'ecosystem.perfect10_client_id',
            null
        );

        $this->authenticateApi($user);

        $this->getJson($this->identityUrl())
            ->assertForbidden()
            ->assertJson([
                'message' => 'Application access denied.',
            ]);
    }

    public function test_user_without_membership_is_rejected(): void
    {
        $user = $this->createUser();

        $this->createApp();

        $this->authenticateApi($user);

        $this->getJson($this->identityUrl())
            ->assertForbidden()
            ->assertJson([
                'message' => 'Application membership required.',
            ]);
    }

    public function test_pending_membership_is_rejected(): void
    {
        $user = $this->createUser();
        $app = $this->createApp();

        $this->createMembership($app, 'pending');

        $this->authenticateApi($user);

        $this->getJson($this->identityUrl())
            ->assertForbidden()
            ->assertJson([
                'message' => 'Application membership required.',
            ]);
    }

    public function test_suspended_membership_is_rejected(): void
    {
        $user = $this->createUser();
        $app = $this->createApp();

        $this->createMembership($app, 'suspended');

        $this->authenticateApi($user);

        $this->getJson($this->identityUrl())
            ->assertForbidden()
            ->assertJson([
                'message' => 'Application membership required.',
            ]);
    }

    public function test_removed_membership_is_rejected(): void
    {
        $user = $this->createUser();
        $app = $this->createApp();

        $this->createMembership($app, 'removed');

        $this->authenticateApi($user);

        $this->getJson($this->identityUrl())
            ->assertForbidden()
            ->assertJson([
                'message' => 'Application membership required.',
            ]);
    }

    public function test_inactive_app_is_rejected(): void
    {
        $user = $this->createUser();
        $app = $this->createApp('maintenance');

        $this->createMembership($app);

        $this->authenticateApi($user);

        $this->getJson($this->identityUrl())
            ->assertForbidden()
            ->assertJson([
                'message' => 'Application unavailable.',
            ]);
    }

    public function test_active_member_receives_central_auth_id(): void
    {
        $user = $this->createUser();
        $app = $this->createApp();

        $this->createMembership($app);

        $this->authenticateApi($user);

        $this->getJson($this->identityUrl())
            ->assertOk()
            ->assertExactJson([
                'auth_id' => self::AUTH_ID,
            ]);
    }
}
