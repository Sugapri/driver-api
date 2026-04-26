<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Pelanggan yang pesan
            $table->foreignId('driver_id')->nullable()->constrained()->onDelete('set null'); // Driver yang ambil order
            
            $table->string('order_type'); // Ride, Food, Send, Shop
            $table->string('status')->default('pending'); // pending, accepted, picked_up, completed, cancelled
            
            // Lokasi Pick Up
            $table->decimal('pickup_lat', 10, 8);
            $table->decimal('pickup_lng', 11, 8);
            $table->text('pickup_address');
            
            // Lokasi Tujuan
            $table->decimal('destination_lat', 10, 8)->nullable();
            $table->decimal('destination_lng', 11, 8)->nullable();
            $table->text('destination_address')->nullable();
            
            $table->text('notes')->nullable();
            $table->decimal('estimated_price', 12, 2)->nullable();
            $table->decimal('final_price', 12, 2)->nullable();
            
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('picked_up_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('orders');
    }
};