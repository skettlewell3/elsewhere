<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'pages.home')
    ->name('home')
;

Route::view('/games', 'pages.games')
    ->name('games')
;

Route::view('/directory', 'pages.directory')
    ->name('directory')
;

Route::view('/profile', 'pages.profile')
    ->name('profile')
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