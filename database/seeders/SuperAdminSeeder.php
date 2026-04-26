<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run()
    {
        User::updateOrCreate(
            ['email' => 'admin@anterin.com'],
            [
                'name' => 'Super Admin Anterin',
                'password' => Hash::make('admin123'),
                'phone' => '08123456789',
                'role' => 'admin'
            ]
        );
    }
}
