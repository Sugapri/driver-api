<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Restaurant extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'address',
        'latitude',
        'longitude',
        'image_url',
        'rating',
        'delivery_time',
        'is_promo'
    ];

    public function foodItems()
    {
        return $this->hasMany(FoodItem::class);
    }
}
