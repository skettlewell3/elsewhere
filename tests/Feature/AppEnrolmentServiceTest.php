<?php

namespace Tests\Feature;

use App\Contracts\AppProvisioner;
use App\Models\AccountUser;
use App\Models\App;
use App\Models\AppMembership;
use App\Services\Apps\AppEnrolmentService;
use App\Services\Apps\AppProvisionerRegistry;
use DomainException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class AppEnrolmentServiceTest extends TestCase
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

    private function createUser(): AccountUser
    {
        return AccountUser::query()->create([
            'auth_id' => self::AUTH_ID,
            'account_id' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa',
            'username' => 'test_player',
        ]);
    }

    private function createApp(
        string $status = 'active',
        string $slug = 'perfect10'
    ): App {
        return App::query()->create([
            'slug' => $slug,
            'name' => $slug === 'perfect10'
                ? 'Perfect10'
                : 'Another App',
            'status' => $status,
        ]);
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

    private function service(
        AppProvisioner $provisioner,
        App $app,
        bool $shouldResolve = true
    ): AppEnrolmentService {
        $registry = Mockery::mock(AppProvisionerRegistry::class);

        if ($shouldResolve) {
            $registry->shouldReceive('resolve')
                ->once()
                ->with($app)
                ->andReturn($provisioner);
        } else {
            $registry->shouldNotReceive('resolve');
        }

        return new AppEnrolmentService($registry);
    }

    public function test_new_membership_is_activated_after_provisioning(): void
    {
        $user = $this->createUser();
        $app = $this->createApp();

        $provisioner = Mockery::mock(AppProvisioner::class);

        $provisioner->shouldReceive('provision')
            ->once()
            ->with($user, $app)
            ->andReturnNull();

        $membership = $this->service(
            $provisioner,
            $app
        )->enrol($user, $app);

        $this->assertSame('active', $membership->status);
        $this->assertNotNull($membership->registered_at);

        $this->assertDatabaseHas('app_memberships', [
            'auth_id' => self::AUTH_ID,
            'app_id' => $app->app_id,
            'status' => 'active',
        ]);

        $this->assertDatabaseCount('app_memberships', 1);
    }

    public function test_active_membership_is_returned_without_provisioning(): void
    {
        $user = $this->createUser();
        $app = $this->createApp();

        $original = $this->createMembership($app, 'active');

        $provisioner = Mockery::mock(AppProvisioner::class);

        $provisioner->shouldNotReceive('provision');

        $membership = $this->service(
            $provisioner,
            $app,
            false
        )->enrol($user, $app);

        $this->assertEquals(
            $original->getKey(),
            $membership->getKey()
        );

        $this->assertSame('active', $membership->status);
        $this->assertDatabaseCount('app_memberships', 1);
    }

    public function test_pending_membership_can_complete_registration(): void
    {
        $user = $this->createUser();
        $app = $this->createApp();

        $original = $this->createMembership($app, 'pending');

        $provisioner = Mockery::mock(AppProvisioner::class);

        $provisioner->shouldReceive('provision')
            ->once()
            ->with($user, $app)
            ->andReturnNull();

        $membership = $this->service(
            $provisioner,
            $app
        )->enrol($user, $app);

        $this->assertEquals(
            $original->getKey(),
            $membership->getKey()
        );

        $this->assertSame('active', $membership->status);
        $this->assertNotNull($membership->registered_at);
        $this->assertDatabaseCount('app_memberships', 1);
    }

    public function test_provisioning_failure_leaves_membership_pending(): void
    {
        $user = $this->createUser();
        $app = $this->createApp();

        $provisioner = Mockery::mock(AppProvisioner::class);

        $provisioner->shouldReceive('provision')
            ->once()
            ->with($user, $app)
            ->andThrow(
                new RuntimeException('Provisioning unavailable')
            );

        try {
            $this->service(
                $provisioner,
                $app
            )->enrol($user, $app);

            $this->fail('Expected provisioning to fail.');
        } catch (RuntimeException $exception) {
            $this->assertSame(
                'Provisioning unavailable',
                $exception->getMessage()
            );
        }

        $this->assertDatabaseHas('app_memberships', [
            'auth_id' => self::AUTH_ID,
            'app_id' => $app->app_id,
            'status' => 'pending',
            'registered_at' => null,
        ]);
    }

    public function test_suspended_membership_cannot_be_reactivated(): void
    {
        $this->assertProtectedMembershipRemainsUnchanged(
            'suspended'
        );
    }

    public function test_removed_membership_cannot_be_reactivated(): void
    {
        $this->assertProtectedMembershipRemainsUnchanged(
            'removed'
        );
    }

    private function assertProtectedMembershipRemainsUnchanged(
        string $status
    ): void {
        $user = $this->createUser();
        $app = $this->createApp();

        $this->createMembership($app, $status);

        $provisioner = Mockery::mock(AppProvisioner::class);

        $provisioner->shouldNotReceive('provision');

        try {
            $this->service(
                $provisioner,
                $app,
                false
            )->enrol($user, $app);

            $this->fail('Expected enrolment to be rejected.');
        } catch (DomainException) {
            // Expected.
        }

        $this->assertDatabaseHas('app_memberships', [
            'auth_id' => self::AUTH_ID,
            'app_id' => $app->app_id,
            'status' => $status,
        ]);

        $this->assertDatabaseCount('app_memberships', 1);
    }

    public function test_inactive_application_cannot_accept_enrolment(): void
    {
        $user = $this->createUser();
        $app = $this->createApp('maintenance');

        $provisioner = Mockery::mock(AppProvisioner::class);

        $provisioner->shouldNotReceive('provision');

        $this->expectException(DomainException::class);

        try {
            $this->service(
                $provisioner,
                $app,
                false
            )->enrol($user, $app);
        } finally {
            $this->assertDatabaseCount('app_memberships', 0);
        }
    }

    public function test_enrolment_is_reusable_for_another_application(): void
    {
        $user = $this->createUser();
        $app = $this->createApp('active', 'another-app');

        $provisioner = Mockery::mock(AppProvisioner::class);

        $provisioner->shouldReceive('provision')
            ->once()
            ->with($user, $app)
            ->andReturnNull();

        $membership = $this->service(
            $provisioner,
            $app
        )->enrol($user, $app);

        $this->assertSame('active', $membership->status);

        $this->assertDatabaseHas('app_memberships', [
            'auth_id' => self::AUTH_ID,
            'app_id' => $app->app_id,
            'status' => 'active',
        ]);
    }
}
