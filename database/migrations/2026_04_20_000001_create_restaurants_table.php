<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('restaurants', function (Blueprint $row) {
            $row->id();
            $row->string('name');
            $row->text('description')->nullable();
            $row->string('address');
            $row->decimal('latitude', 10, 8);
            $row->decimal('longitude', 11, 8);
            $row->string('image_url')->nullable();
            $row->decimal('rating', 3, 2)->default(4.5);
            $row->string('delivery_time')->default('20-30 min');
            $row->boolean('is_promo')->default(false);
            $row->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('restaurants');
    }
};
