<?php

namespace App\Http\Middleware;

use App\Services\Auth\CurrentAccountService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RefreshSupabaseSession
{
    public function __construct(
        private readonly CurrentAccountService $currentAccount
    ) {}

    public function handle(
        Request $request,
        Closure $next
    ): Response {
        if (Auth::guard('web')->check()) {
            if (! $this->currentAccount->ensureFreshSession()) {
                Auth::guard('web')->logout();

                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }
        }

        return $next($request);
    }
}
