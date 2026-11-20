<?php

use App\Http\Controllers\Api\AuthenticatedIdentityController;
use Illuminate\Support\Facades\Route;
use Laravel\Passport\Http\Middleware\CheckToken;

Route::middleware([
    'auth:api',
    CheckToken::using('identity:read'),
])->group(function (): void {
    Route::get(
        '/auth/me',
        AuthenticatedIdentityController::class
    )->name('api.auth.me');
});
