<?php

namespace Database\Seeders;

use App\Models\SwadayaProgram;
use Illuminate\Database\Seeder;

class SwadayaProgramSeeder extends Seeder
{
    public function run(): void
    {
        SwadayaProgram::create([
            'name' => 'Green Driver Challenge',
            'description' => 'Selesaikan 50 order dengan rating 5 bintang',
            'reward' => 50000,
            'target' => 50,
            'deadline_days' => 30,
            'terms_conditions' => 'Minimal rating 4.8, tidak ada cancel',
        ]);

        SwadayaProgram::create([
            'name' => 'Safety Champion',
            'description' => 'Tanpa kecelakaan selama 100 order',
            'reward' => 100000,
            'target' => 100,
            'deadline_days' => 60,
            'terms_conditions' => 'Tidak ada laporan kecelakaan',
        ]);
    }
}