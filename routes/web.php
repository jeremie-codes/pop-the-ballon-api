<?php

use App\Http\Controllers\Api\GoogleAuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return [
        'status' => 'success',
        'message' => 'Welcome to Pop The Ballon Platform',
    ];
});

Route::get('/google-mobile-result', function () {
    return view('auth');
})->name('auth.google-mobile-result');

Route::prefix('auth')->group(function () {
    Route::get('/google/redirect', [GoogleAuthController::class, 'redirect']);
    Route::get('/google/callback', [GoogleAuthController::class, 'callback']);
});
