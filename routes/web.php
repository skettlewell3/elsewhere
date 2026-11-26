<?php

use App\Http\Controllers\AppEnrolmentController;
use App\Http\Controllers\AppLaunchController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\PasswordRecoveryController;
use App\Http\Controllers\Auth\RegistrationController;
use App\Http\Controllers\BusinessController;
use App\Http\Controllers\DirectoryController;
use App\Http\Controllers\DiscoverController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/login', function () {
    if (Auth::guard('web')->check()) {
        return redirect()->route('directory');
    }

    return view('pages.auth.login');
})->name('login');

Route::post('/login', [AuthController::class, 'login'])
    ->name('login.submit');

Route::post('/logout', [AuthController::class, 'logout'])
    ->name('logout');

Route::get('/auth/logout/perfect10', function () {
    return view('auth.logout-perfect10');
})->name('auth.logout.perfect10');

Route::get(
    '/forgot-password',
    [PasswordRecoveryController::class, 'requestForm']
)->name('password.request');

Route::post(
    '/forgot-password',
    [PasswordRecoveryController::class, 'requestReset']
)
    ->middleware('throttle:3,10')
    ->name('password.email');

Route::get(
    '/reset-password',
    [PasswordRecoveryController::class, 'resetForm']
)->name('password.reset');

Route::post(
    '/reset-password',
    [PasswordRecoveryController::class, 'resetPassword']
)
    ->middleware('throttle:5,1')
    ->name('password.update');

Route::view('/register', 'pages.auth.register')
    ->name('register');

Route::post(
    '/register',
    [RegistrationController::class, 'store']
)
    ->middleware('throttle:5,1')
    ->name('register.store');

Route::get('/', function () {
    return redirect()->route('directory');
});

Route::get('/directory', [DirectoryController::class, 'index'])
    ->name('directory');

Route::get(
    '/directory/businesses/{business}',
    [BusinessController::class, 'showBusiness']
)->name('directory.businesses.overview');

Route::get(
    '/directory/businesses/{location:slug}/{business}',
    [BusinessController::class, 'showBranch']
)->name('directory.businesses.show');

Route::view('/dashboard', 'pages.dashboard')
    ->name('dashboard');

Route::get('/discover', [DiscoverController::class, 'index'])
    ->name('discover');

Route::get('/apps/{app:slug}/enrol', [
    AppEnrolmentController::class,
    'show',
])->name('apps.enrol');

Route::post('/apps/{app:slug}/enrol', [
    AppEnrolmentController::class,
    'store',
])->name('apps.enrol.store');

Route::get('/apps/{app:slug}/launch', [
    AppLaunchController::class,
    'launch',
])->name('apps.launch');

Route::post('/theme', function () {
    session([
        'theme' => request('theme'),
    ]);

    return back();
});

Route::post('/mode', function () {
    session([
        'mode' => request('mode'),
    ]);

    return back();
});

Route::get('/cards', function () {
    return view('cards.index');
})->name('cards.index');

Route::get('/cards/{card}', function (string $card) {
    return view('cards.show', [
        'cardSlug' => $card,
    ]);
})->name('cards.show');

Route::get('/cards/{card}/info', function (string $card) {
    return view('cards.info', [
        'cardSlug' => $card,
    ]);
})->name('cards.info');
