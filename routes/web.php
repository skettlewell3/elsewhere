<?php

use App\Http\Controllers\BusinessController;
use App\Http\Controllers\DirectoryController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('directory');
});

Route::get('/directory', [DirectoryController::class, 'index'])
    ->name('directory')
;

Route::get(
    '/directory/businesses/{business}',
    [BusinessController::class, 'showBusiness']
)->name('directory.businesses.overview');

Route::get(
    '/directory/businesses/{location:slug}/{business}',
    [BusinessController::class, 'showBranch']
)->name('directory.businesses.show');

Route::view('/dashboard', 'pages.dashboard')
    ->name('dashboard')
;

Route::view('/discover', 'pages.discover')
    ->name('discover')
;

Route::post('/theme', function () {

    session([
        'theme' => request('theme')
    ]);

    return back();

});

Route::post('/mode', function () {

    session([
        'mode' => request('mode')
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