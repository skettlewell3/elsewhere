<?php

namespace App\Http\Controllers;

use App\Models\App;
use App\Models\AppMembership;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

class DiscoverController extends Controller
{
    public function index(): View
    {
        $apps = App::query()
            ->whereIn('status', [
                'active',
                'coming_soon',
                'maintenance',
            ])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $user = Auth::guard('web')->user();

        $memberships = collect();

        if ($user !== null) {
            $memberships = AppMembership::query()
                ->where('auth_id', $user->auth_id)
                ->whereIn('app_id', $apps->pluck('app_id'))
                ->get()
                ->keyBy('app_id');
        }

        return view('pages.discover', [
            'apps' => $apps,
            'isLoggedIn' => $user !== null,
            'memberships' => $memberships,
        ]);
    }
}
