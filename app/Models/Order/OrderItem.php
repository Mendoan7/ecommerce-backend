<?php

namespace App\Models\Order;

use App\Models\Product\Product;
use App\Models\Product\Review;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'variations',
        'qty',
        'note',
        'price',
        'total',
        'weight',
    ];

    protected $casts = [
        'variations' => 'array',
        'price' => 'float',
        'total' => 'float',
        'weight' => 'float',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function review()
    {
        return $this->hasOne(Review::class, 'order_item_id');
    }

    public function getApiResponseAttribute()
    {
        return [
            'uuid' => $this->uuid,
            'product' => $this->product->api_response_excerpt,
            'variations' => $this->variations,
            'qty' => $this->qty,
            'note' => $this->note,
            'price' => $this->price,
            'total' => $this->total,
            'weight' => $this->weight,
            'is_reviewed' => $this->review()->count() > 0 ? true : false,
        ];
    }

    public static function boot()
    {
        parent::boot();

        static::creating(function ($orderItem) {
            $orderItem->price = $orderItem->product->price_sale ?? $orderItem->product->price;
            $orderItem->weight = $orderItem->product->weight;
            $orderItem->total = $orderItem->price * $orderItem->qty;
        });
    }
}
