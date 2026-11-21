<?php

namespace App\Services\Apps;

use App\Models\AccountUser;
use App\Models\App;
use App\Models\AppMembership;
use DomainException;

class AppEnrolmentService
{
    public function __construct(
        private readonly AppProvisionerRegistry $provisioners
    ) {}

    public function enrol(
        AccountUser $user,
        App $app
    ): AppMembership {
        if ($app->status !== 'active') {
            throw new DomainException(
                'This application is not accepting registrations.'
            );
        }

        /*
         * Find or create the user's membership.
         *
         * The database enforces uniqueness on
         * (auth_id, app_id).
         *
         * A new membership starts as pending.
         */
        $membership = AppMembership::query()->firstOrCreate(
            [
                'auth_id' => $user->auth_id,
                'app_id' => $app->app_id,
            ],
            [
                'status' => 'pending',
                'registered_at' => null,
            ]
        );

        /*
         * Existing active memberships need no provisioning.
         */
        if ($membership->status === 'active') {
            return $membership;
        }

        /*
         * Suspended and removed memberships cannot be
         * automatically reactivated.
         */
        if ($membership->status !== 'pending') {
            throw new DomainException(
                'This application membership cannot be enrolled.'
            );
        }

        /*
         * Provision the app-specific profile.
         *
         * A failure leaves the membership pending,
         * allowing the operation to be retried.
         */
        $provisioner = $this->provisioners->resolve($app);

        $provisioner->provision($user, $app);

        /*
         * Activate only if the membership is still pending.
         *
         * Never overwrite a concurrent suspension or removal.
         */
        $updated = AppMembership::query()
            ->whereKey($membership->getKey())
            ->where('status', 'pending')
            ->update([
                'status' => 'active',
                'registered_at' => now(),
            ]);

        $membership = $membership->fresh();

        if ($updated === 0 && $membership?->status !== 'active') {
            throw new DomainException(
                'Application membership changed during registration.'
            );
        }

        if ($membership?->status !== 'active') {
            throw new DomainException(
                'Application membership could not be activated.'
            );
        }

        return $membership;
    }
}
