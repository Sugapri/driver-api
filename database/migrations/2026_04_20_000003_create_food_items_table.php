<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('food_items', function (Blueprint $row) {
            $row->id();
            $row->foreignId('restaurant_id')->constrained()->onDelete('cascade');
            $row->foreignId('food_category_id')->constrained()->onDelete('cascade');
            $row->string('name');
            $row->text('description')->nullable();
            $row->decimal('price', 12, 2);
            $row->string('image_url')->nullable();
            $row->boolean('is_available')->default(true);
            $row->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('food_items');
    }
};
