<?php

use Illuminate\Support\Facades\Route;

Route::get('/login', function () {
    return response()->json([
        'success' => false,
        'message' => 'ابتدا وارد شوید',
    ], 401);
})->name('login');
