<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Driver extends Model
{
    use HasFactory;

    //protected $table = 'drivers';

    protected $fillable = [
        'user_id',
        'plate_number',
        'level',
        'level_number',
        'total_orders',
        'rating',
        'performance',
        'balance',
        'capital',
        'is_online',
        'auto_bid',
        'services',
        'latitude',
        'longitude',
        'last_active',
    ];

    protected $casts = [
        'services' => 'array',
        'is_online' => 'boolean',
        'auto_bid' => 'boolean',
        'rating' => 'decimal:2',
        'balance' => 'decimal:2',
        'capital' => 'decimal:2',
    ];

    // ✅ Relasi ke User (INI YANG BENAR)
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}