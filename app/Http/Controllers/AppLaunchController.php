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
         *
         * redirect()->guest() stores the launch URL
         * as Laravel's intended destination.
         *
         * After login, AuthController's existing
         * redirect()->intended('/') resumes here.
         */
        if (! Auth::guard('web')->check()) {
            return redirect()->guest(
                route('login')
            );
        }

        $user = Auth::guard('web')->user();

        /*
         * Membership belongs to the individual
         * Elsewhere user, not their parent account.
         */
        $membership = AppMembership::query()
            ->where('auth_id', $user->auth_id)
            ->where('app_id', $app->app_id)
            ->first();

        /*
         * An existing active member will eventually
         * be sent through the SSO handoff.
         *
         * Do not redirect directly to the app until
         * that process exists.
         */
        if ($membership?->status === 'active') {
            return redirect()
                ->route('discover')
                ->with(
                    'app_launch_message',
                    "{$app->name} is ready in your Elsewhere account. The app login connection is not available yet."
                );
        }

        /*
         * A new member will eventually enter the
         * application-specific onboarding process.
         */
        if ($membership === null ||
            $membership->status === 'removed'
        ) {
            return redirect()
                ->route('discover')
                ->with(
                    'app_launch_message',
                    "You can create a {$app->name} profile once application registration is connected."
                );
        }

        if ($membership->status === 'pending') {
            return redirect()
                ->route('discover')
                ->with(
                    'app_launch_message',
                    "Your {$app->name} registration is pending."
                );
        }

        if ($membership->status === 'suspended') {
            return redirect()
                ->route('discover')
                ->with(
                    'app_launch_message',
                    "Your {$app->name} access is currently suspended."
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
