<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('countries', function (Blueprint $table) {
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('map_zoom', 4, 2)->nullable();
        });

        Schema::table('locations', function (Blueprint $table) {
            $table->decimal('map_zoom', 4, 2)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('countries', function (Blueprint $table) {
            $table->dropColumn([
                'latitude',
                'longitude',
                'map_zoom',
            ]);
        });

        Schema::table('locations', function (Blueprint $table) {
            $table->dropColumn('map_zoom');
        });
    }
};