<?php

use App\Http\Controllers\DirectoryController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DirectoryController::class, 'index'])
    ->name('directory')
;

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