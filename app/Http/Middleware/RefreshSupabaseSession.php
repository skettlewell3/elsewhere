<?php

namespace App\Http\Middleware;

use App\Services\Auth\CurrentAccountService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RefreshSupabaseSession
{
    public function __construct(
        private readonly CurrentAccountService $currentAccount
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (session()->has('auth_user_id')) {
            $this->currentAccount->ensureFreshSession();
        }

        return $next($request);
    }
}
