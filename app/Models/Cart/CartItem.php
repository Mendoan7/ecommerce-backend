<?php

namespace App\Models\Cart;

use App\Models\Product\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'cart_id',
        'product_id',
        'variations',
        'qty',
        'note',
    ];

    protected $casts = [
        'variations' => 'array',
    ];

    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function getVariationsAttribute($value)
    {
        return json_decode($value);
    }

    public function setVariationsAttribute($value)
    {
        $this->attributes['variations'] = json_encode($value);
    }

    public function getTotalAttribute()
    {
        return ($this->product->price_sale ?? $this->product->price) * $this->qty;
    }

    public function getApiResponseAttribute()
    {
        return [
            'uuid' => $this->uuid,
            'product' => $this->product->api_response_excerpt,
            'variations' => $this->variations,
            'qty' => $this->qty,
            'note' => $this->note,
            'total' => $this->total,
        ];
    }
}
