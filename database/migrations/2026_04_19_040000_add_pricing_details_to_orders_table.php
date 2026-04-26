<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('distance_km', 8, 2)->after('pickup_address')->default(0);
            $table->decimal('app_fee', 12, 2)->after('estimated_price')->default(0);
            $table->decimal('driver_total', 12, 2)->after('app_fee')->default(0);
        });
    }

    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['distance_km', 'app_fee', 'driver_total']);
        });
    }
};
