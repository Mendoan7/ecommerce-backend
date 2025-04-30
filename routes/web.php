<?php

use App\Http\Controllers\MidtransController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/midtrans/callback', [MidtransController::class, 'callback']);
