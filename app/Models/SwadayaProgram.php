<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SwadayaProgram extends Model
{
    protected $fillable = [
        'name',
        'description',
        'reward',
        'target',
        'deadline_days',
        'terms_conditions',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function participants()
    {
        return $this->hasMany(SwadayaParticipant::class);
    }
}