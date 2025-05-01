<?php

use App\Http\Controllers\MidtransController;
use App\Mail\NewOrderToSeller;
use App\Models\Order\Order;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/testing', function () {
    $order = Order::orderBy('id', 'desc')->first();

    return new NewOrderToSeller($order);
});

Route::post('/midtrans/callback', [MidtransController::class, 'callback']);
