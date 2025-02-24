<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use App\Models\Address\Address;
use App\Models\Product\Review;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'phone',
        'store_name',
        'gender',
        'birth_date',
        'photo',
        'email',
        'email_verified_at',
        'password',
        'otp_register',
        'social_media_provider',
        'social_media_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function getApiResponseAttribute()
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'photo_url' => $this->photo_url,
            'username' => $this->username,
            'phone' => $this->phone,
            'store_name' => $this->store_name,
            'gender' => $this->gender,
            'birth_date' => $this->birth_date,
        ];
    }
    
    public function getApiResponseAsSellerAttribute()
    {
        $productId = $this->product()->pluck('id');

        return [
            'username' => $this->username,
            'store_name' => $this->store_name,
            'photo_url' => $this->photo_url,
            'product_count' => $this->product()->count(),
            'rating_count' => Review::whereIn('product_id', $productId)->count(),
            'join_date' => $this->created_at->diffForHumans(),
            'send_from' => optional($this->address()->where('is_default', true)->first())->getApiResponseAttribute()
        ];
    }



    public function getPhotoUrlAttribute()
    {
        if (is_null($this->photo)) {
            return null;
        }
        return asset('storage/' . $this->photo);
    }

    public function addresses()
    {
        return $this->hasMany(Address::class);
    }
}
