<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FoodItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'restaurant_id',
        'food_category_id',
        'name',
        'description',
        'price',
        'image_url',
        'is_available'
    ];

    public function restaurant()
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function category()
    {
        return $this->belongsTo(FoodCategory::class, 'food_category_id');
    }
}
