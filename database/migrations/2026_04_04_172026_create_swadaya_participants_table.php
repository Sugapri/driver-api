<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('swadaya_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_id')->constrained()->onDelete('cascade');
            $table->foreignId('swadaya_program_id')->constrained()->onDelete('cascade');
            $table->integer('progress')->default(0);
            $table->string('status')->default('active'); // active, completed, expired
            $table->timestamp('joined_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('deadline_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('swadaya_participants');
    }
};