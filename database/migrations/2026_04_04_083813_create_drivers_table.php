<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('plate_number')->unique();
            $table->string('level')->default('Bronze');
            $table->integer('level_number')->default(1);
            $table->integer('total_orders')->default(0);
            $table->decimal('rating', 3, 2)->default(0.00);
            $table->integer('performance')->default(0);
            $table->decimal('balance', 12, 2)->default(0);
            $table->decimal('capital', 12, 2)->default(0);
            $table->boolean('is_online')->default(false);
            $table->boolean('auto_bid')->default(false);
            $table->json('services')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('drivers');
    }
};