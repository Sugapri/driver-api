<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Add role to users
        if (!Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('role')->default('user')->after('email');
            });
        }

        // Add status and location to drivers
        Schema::table('drivers', function (Blueprint $table) {
            if (!Schema::hasColumn('drivers', 'status')) {
                $table->enum('status', ['pending', 'verified', 'rejected'])->default('pending')->after('user_id');
            }
            if (!Schema::hasColumn('drivers', 'latitude')) {
                $table->decimal('latitude', 10, 8)->nullable()->after('services');
            }
            if (!Schema::hasColumn('drivers', 'longitude', 11, 8)) {
                $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
            }
            if (!Schema::hasColumn('drivers', 'last_active')) {
                $table->timestamp('last_active')->nullable()->after('longitude');
            }
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
        Schema::table('drivers', function (Blueprint $table) {
            $table->dropColumn(['status', 'latitude', 'longitude', 'last_active']);
        });
    }
};
