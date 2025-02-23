<?php

namespace App\Models\Address;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'is_default',
        'receiver_name',
        'receiver_phone',
        'city_id',
        'district',
        'postal_code',
        'detail_address',
        'address_note',
        'type',
    ];

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function getApiResponseAttribute()
    {
        return [
            'uuid' => $this->uuid,
            'is_default' => (boolean) $this->is_default,
            'receiver_name' => $this->receiver_name,
            'receiver_phone' => $this->receiver_phone,
            'city' => $this->city->api_response,
            'district' => $this->district,
            'postal_code' => $this->postal_code,
            'detail_address' => $this->detail_address,
            'address_note' => $this->address_note,
            'type' => $this->type,
        ];
    }

}
