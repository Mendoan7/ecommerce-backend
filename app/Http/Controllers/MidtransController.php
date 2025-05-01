<?php

namespace App\Http\Controllers;

use App\Mail\NewOrderToSeller;
use App\Models\Order\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class MidtransController extends Controller
{
    public function callback()
    {
        \Midtrans\Config::$serverKey = config('services.midtrans.server_key');
        \Midtrans\Config::$isProduction = config('app.env') == 'production';

        $notification = new \Midtrans\Notification();
        $transaction = $notification->transaction_status;
        $orderId = $notification->order_id;

        $order = Order::where('uuid', $orderId)->firstOrFail();

        if ($transaction == 'capture' || $transaction == 'settlement') {
            DB::transaction(function() use($order) {
                $order->status()->create([
                    'status' => 'paid',
                    'description' => 'Pembayaran berhasil, menunggu proses pengiriman'
                ]);

                $order->update([
                    'is_paid' => true,
                    'payment_expired_at' => null
                ]);

                foreach ($order->items as $item) {
                    $item->product->decrement('stock', $item->qty);
                }

                \Mail::to($order->seller->email)->send(new NewOrderToSeller($order));
            });

        } elseif ($transaction == 'cancel' || $transaction == 'deny') {
            $order->status()->create([
                'status' => 'failed',
                'description' => 'Pembayaran gagal'
            ]);
        }
    }
}
