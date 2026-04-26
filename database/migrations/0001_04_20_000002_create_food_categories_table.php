<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('food_categories', function (Blueprint $row) {
            $row->id();
            $row->string('name'); // Cepat Saji, Tradisional, Kopi, Sehat
            $row->string('icon')->nullable();
            $row->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('food_categories');
    }
};
