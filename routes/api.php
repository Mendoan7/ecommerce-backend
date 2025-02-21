<?php

use App\Http\Controllers\AuthenticationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Route::get('/user', function (Request $request) {
//     return $request->user();
// })->middleware('auth:sanctum');

Route::post('/register', [AuthenticationController::class, 'register']);
Route::post('/resend-otp', [AuthenticationController::class, 'resendOtp']);
Route::post('/check-otp', [AuthenticationController::class, 'verifyOtp']);
Route::post('/verify-register', [AuthenticationController::class, 'verifyRegister']);
Route::post('/login', [AuthenticationController::class, 'login']);