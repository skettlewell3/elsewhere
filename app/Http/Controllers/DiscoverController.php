<?php

namespace App\Http\Controllers;

use App\Models\App;
use Illuminate\Contracts\View\View;

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

        return view('pages.discover', [
            'apps' => $apps,
        ]);
    }
}
