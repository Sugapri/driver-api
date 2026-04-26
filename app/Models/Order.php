<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'driver_id',
        'order_type',
        'status',
        'pickup_lat',
        'pickup_lng',
        'pickup_address',
        'destination_lat',
        'destination_lng',
        'destination_address',
        'notes',
        'estimated_price',
        'app_fee',
        'driver_total',
        'final_price',
        'distance_km',
        'accepted_at',
        'picked_up_at',
        'completed_at',
        'cancelled_at',
        'package_details',
        // new fields for rating
        'rating',
        'rating_comment',
        'payment_method',
    ];

    protected $casts = [
        'pickup_lat' => 'decimal:8',
        'pickup_lng' => 'decimal:8',
        'destination_lat' => 'decimal:8',
        'destination_lng' => 'decimal:8',
        'estimated_price' => 'decimal:2',
        'app_fee' => 'decimal:2',
        'driver_total' => 'decimal:2',
        'final_price' => 'decimal:2',
        'distance_km' => 'decimal:2',
        'accepted_at' => 'datetime',
        'picked_up_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'package_details' => 'array',
        'rating' => 'integer',
        'rating_comment' => 'string',
    ];

    // Relations
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }
}
?>