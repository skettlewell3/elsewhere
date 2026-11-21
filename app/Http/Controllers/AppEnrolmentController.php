<?php

namespace App\Http\Controllers;

use App\Models\App;
use App\Models\AppMembership;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

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
        ]);
    }
}
