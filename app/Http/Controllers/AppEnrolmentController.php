<?php

namespace App\Http\Controllers;

use App\Models\App;
use App\Models\AppMembership;
use App\Services\Apps\AppEnrolmentService;
use DomainException;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Throwable;

class AppEnrolmentController extends Controller
{
    public function show(App $app): View|RedirectResponse
    {
        if (! Auth::guard('web')->check()) {
            return redirect()->guest(route('login'));
        }

        if ($app->status !== 'active') {
            return redirect()
                ->route('discover')
                ->with(
                    'app_launch_message',
                    "{$app->name} is not currently available."
                );
        }

        $user = Auth::guard('web')->user();

        $membership = AppMembership::query()
            ->where('auth_id', $user->auth_id)
            ->where('app_id', $app->app_id)
            ->first();

        if ($membership?->status === 'active') {
            return redirect()->route('apps.launch', $app);
        }

        if ($membership?->status === 'suspended') {
            return redirect()
                ->route('discover')
                ->with(
                    'app_launch_message',
                    "Your {$app->name} access is currently suspended."
                );
        }

        if ($membership?->status === 'removed') {
            return redirect()
                ->route('discover')
                ->with(
                    'app_launch_message',
                    "Your {$app->name} membership cannot be automatically reactivated."
                );
        }

        return view('pages.apps.enrol', [
            'app' => $app,
            'membership' => $membership,
            'enrolmentEnabled' => (bool) config(
                'app_enrolment.enabled',
                false
            ),
        ]);
    }

    public function store(
        App $app,
        AppEnrolmentService $enrolment
    ): RedirectResponse {
        /*
         * Registration stays disabled until both applications
         * and their database migrations are ready.
         */
        abort_unless(
            config('app_enrolment.enabled', false),
            503,
            'Application enrolment is not currently available.'
        );

        if (! Auth::guard('web')->check()) {
            return redirect()->guest(route('login'));
        }

        $user = Auth::guard('web')->user();

        try {
            $enrolment->enrol($user, $app);
        } catch (DomainException $exception) {
            return redirect()
                ->route('discover')
                ->with(
                    'app_launch_message',
                    'This application registration cannot be completed.'
                );
        } catch (Throwable $exception) {
            report($exception);

            return redirect()
                ->route('apps.enrol', $app)
                ->with(
                    'app_enrolment_error',
                    'Registration could not be completed. Please try again later.'
                );
        }

        /*
         * Provisioning succeeded and Elsewhere activated
         * the membership. Resume the normal app launch.
         */
        return redirect()->route('apps.launch', $app);
    }
}
