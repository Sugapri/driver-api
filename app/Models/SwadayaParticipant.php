<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SwadayaParticipant extends Model
{
    protected $fillable = [
        'driver_id',
        'swadaya_program_id',
        'progress',
        'status',
        'joined_at',
        'completed_at',
        'deadline_at',
    ];

    protected $casts = [
        'joined_at' => 'datetime',
        'completed_at' => 'datetime',
        'deadline_at' => 'datetime',
    ];

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

    public function program()
    {
        return $this->belongsTo(SwadayaProgram::class);
    }
}