<?php

namespace App\Models\Order;

use App\Models\Address\Address;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'invoice_number',
        'user_id',
        'seller_id',
        'address_id',
        'courier',
        'courier_type',
        'courier_estimation',
        'courier_price',
        'voucher_id',
        'voucher_value',
        'voucher_cashback',
        'service_fee',
        'total',
        'pay_with_coin',
        'payment_method',
        'total_payment',
        'payment_expired_at',
        'is_paid',
        'virtual_account_number',
        'qris_image_url',
    ];

    protected $casts = [
        'courier_price' => 'float',
        'voucher_value' => 'float',
        'voucher_cashback' => 'float',
        'service_fee' => 'float',
        'total' => 'float',
        'pay_with_coin' => 'float',
        'total_payment' => 'float',
        'payment_expired_at' => 'datetime',
        'is_paid' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function address()
    {
        return $this->belongsTo(Address::class);
    }

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function status()
    {
        return $this->hasMany(OrderStatus::class);
    }

    public function lastStatus()
    {
        return $this->hasOne(OrderStatus::class)->latestOfMany();
    }

    // 
    public function markAsDone()
    {
        DB::transaction(function(){
            // Add status
            $this->status()->create([
                'status' => 'done',
                'description' => 'Pesanan selesai',
            ]);

            // Add balance to seller
            $this->seller->deposit($this->items()->sum('total'), [
                'description' => 'Penjualan produk ' . $this->invoice_number,
            ]);
        });
    }

    // Response
    public function getApiResponseAttribute()
    {
        return [
            'uuid' => $this->uuid,
            'invoice_number' => $this->invoice_number,
            'seller' => $this->seller->getApiResponseAsSellerAttribute(),
            'address' => $this->address->getApiResponseAttribute(),
            'subtotal' => (float) $this->items->sum('total'),
            'courier' => $this->courier,
            'courier_type' => $this->courier_type,
            'courier_estimation' => $this->courier_estimation,
            'courier_price' => $this->courier_price,
            'voucher_value' => $this->voucher_value,
            'voucher_cashback' => $this->voucher_cashback,
            'service_fee' => $this->service_fee,
            'total' => $this->total,
            'pay_with_coin' => $this->pay_with_coin,
            'payment_method' => $this->payment_method,
            'total_payment' => $this->total_payment,
            'payment_expired_at' => $this->payment_expired_at,
            'is_paid' => $this->is_paid,
            'virtual_account_number' => $this->virtual_account_number,
            'qris_image_url' => $this->qris_image_url,
            'last_status' => $this->lastStatus->getApiResponseAttribute(),
            'items' => $this->items->map->getApiResponseAttribute(),
        ];
    }

    public function getApiResponseDetailAttribute()
    {
        return [
            'uuid' => $this->uuid,
            'invoice_number' => $this->invoice_number,
            'seller' => $this->seller->getApiResponseAsSellerAttribute(),
            'address' => $this->address->getApiResponseAttribute(),
            'subtotal' => (float) $this->items()->sum('total'),
            'courier' => $this->courier,
            'courier_type' => $this->courier_type,
            'courier_estimation' => $this->courier_estimation,
            'courier_price' => $this->courier_price,
            'voucher_value' => $this->voucher_value,
            'voucher_cashback' => $this->voucher_cashback,
            'service_fee' => $this->service_fee,
            'total' => $this->total,
            'pay_with_coin' => $this->pay_with_coin,
            'payment_method' => $this->payment_method,
            'total_payment' => $this->total_payment,
            'payment_expired_at' => $this->payment_expired_at,
            'is_paid' => $this->is_paid,
            'virtual_account_number' => $this->virtual_account_number,
            'qris_image_url' => $this->qris_image_url,
            'items' => $this->items->map->getApiResponseAttribute(),
            'status' => $this->status->map->getApiResponseAttribute(),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }

    public function getApiResponseSellerAttribute()
    {
        return [
            'uuid' => $this->uuid,
            'invoice_number' => $this->invoice_number,
            'buyer' => $this->user->getApiResponseAsBuyerAttribute(),
            'address' => $this->address->getApiResponseAttribute(),
            'total' => (float) $this->items()->sum('total'),
            'courier' => $this->courier,
            'courier_type' => $this->courier_type,
            'items' => $this->items->map->getApiResponseAttribute(),
            'status' => $this->status->map->getApiResponseAttribute(),
            'last_status' => $this->status->sortByDesc('id')->first()->getApiResponseAttribute(),
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }

    public function generatePayment()
    {
        // Generate midtrans transaction
        \Midtrans\Config::$serverKey = config('services.midtrans.server_key');
        \Midtrans\Config::$isProduction = config('app.env') == 'production';

        $params = [
            'transaction_details' => [
                'order_id' => $this->uuid,
                'gross_amount' => round($this->total_payment),
            ],
            'customer_details' => [
                'first_name' => $this->user->name,
                'email' => $this->user->email,
            ],
        ];

        if ($this->payment_method == 'qris') {
            $params['payment_type'] = 'qris';
        } elseif ($this->payment_method == 'bca_va') {
            $params['payment_type'] = 'bank_transfer';
            $params['bank_transfer'] = [
                'bank' => 'bca',
            ];
        }

        $response = \Midtrans\CoreApi::charge($params);

        $this->update([
            'payment_expired_at' => now()->addHour(),
            'virtual_account_number' => $response->va_numbers[0]->va_number ?? null,
            'qris_image_url' => $response->actions[0]->url ?? null,
        ]);
    }

    public function scopeIsPaid($query)
    {
        return $query->where('is_paid', true);
    }

    public static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            // Generate invoice number before saving
            $order->invoice_number = 'INV-' . $order->user_id . '-' . date('YmdHis');
        });
    }
}
