<?php

namespace App\Http\Controllers;

use App\Models\App;
use App\Models\AppMembership;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class AppLaunchController extends Controller
{
    public function launch(App $app): RedirectResponse
    {
        /*
         * Only active applications can be launched.
         */
        if ($app->status !== 'active') {
            return redirect()
                ->route('discover')
                ->with(
                    'app_launch_message',
                    "{$app->name} is not currently available."
                );
        }

        /*
         * Guests must authenticate through Elsewhere.
         * The intended URL resumes after login.
         */
        if (! Auth::guard('web')->check()) {
            return redirect()->guest(
                route('login')
            );
        }

        $user = Auth::guard('web')->user();

        /*
         * Membership belongs to the individual Elsewhere
         * user, not their parent account.
         */
        $membership = AppMembership::query()
            ->where('auth_id', $user->auth_id)
            ->where('app_id', $app->app_id)
            ->first();

        /*
         * Active members may launch supported applications.
         */
        if ($membership?->status === 'active') {
            if ($app->slug === 'perfect10') {
                $launchUrl = config(
                    'elsewhere_oauth.perfect10_url'
                );

                abort_unless(
                    is_string($launchUrl) &&
                    filter_var($launchUrl, FILTER_VALIDATE_URL) &&
                    in_array(
                        parse_url($launchUrl, PHP_URL_SCHEME),
                        ['http', 'https'],
                        true
                    ) &&
                    ! parse_url(
                        $launchUrl,
                        PHP_URL_USER
                    ) &&
                    ! parse_url(
                        $launchUrl,
                        PHP_URL_PASS
                    ),
                    503,
                    'Perfect10 launch URL is not configured.'
                );

                return redirect()->away(
                    rtrim($launchUrl, '/').'/'
                );
            }

            return redirect()
                ->route('discover')
                ->with(
                    'app_launch_message',
                    "{$app->name} is not yet connected to Elsewhere sign-in."
                );
        }

        /*
         * New users must confirm enrolment.
         * Pending users can resume registration.
         */
        if (
            $membership === null ||
            $membership->status === 'pending'
        ) {
            return redirect()->route(
                'apps.enrol',
                $app
            );
        }

        /*
         * Suspended and removed memberships
         * cannot automatically enrol again.
         */
        if ($membership->status === 'suspended') {
            return redirect()
                ->route('discover')
                ->with(
                    'app_launch_message',
                    "Your {$app->name} access is currently suspended."
                );
        }

        if ($membership->status === 'removed') {
            return redirect()
                ->route('discover')
                ->with(
                    'app_launch_message',
                    "Your {$app->name} membership cannot be automatically reactivated."
                );
        }

        return redirect()
            ->route('discover')
            ->with(
                'app_launch_message',
                "{$app->name} is unavailable."
            );
    }
}
