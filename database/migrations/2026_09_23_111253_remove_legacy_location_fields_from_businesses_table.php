<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            /*
            |--------------------------------------------------------------------------
            | Remove constraints tied to legacy business-level geography
            |--------------------------------------------------------------------------
            */

            $table->dropUnique(
                'businesses_canonical_location_slug_unique'
            );

            $table->dropForeign(
                'businesses_canonical_location_id_foreign'
            );

            $table->dropForeign(
                'businesses_location_id_foreign'
            );

            /*
            |--------------------------------------------------------------------------
            | Remove legacy geographic fields
            |--------------------------------------------------------------------------
            */

            $table->dropColumn([
                'location_id',
                'canonical_location_id',
                'latitude',
                'longitude',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->foreignId('location_id')
                ->nullable()
                ->constrained('locations')
                ->nullOnDelete();

            $table->unsignedBigInteger('canonical_location_id')
                ->nullable();

            $table->decimal('latitude', 10, 7)
                ->nullable();

            $table->decimal('longitude', 10, 7)
                ->nullable();

            $table->foreign('canonical_location_id')
                ->references('id')
                ->on('locations')
                ->restrictOnDelete();
        });

        /*
        |--------------------------------------------------------------------------
        | Restore legacy values from the primary business location
        |--------------------------------------------------------------------------
        */

        DB::statement('
            update businesses b
            set
                location_id = bl.location_id,
                canonical_location_id = bl.canonical_location_id,
                latitude = bl.latitude,
                longitude = bl.longitude
            from business_locations bl
            where bl.business_id = b.id
              and bl.is_primary = true
        ');

        Schema::table('businesses', function (Blueprint $table) {
            $table->unsignedBigInteger('canonical_location_id')
                ->nullable(false)
                ->change();

            $table->unique(
                ['canonical_location_id', 'slug'],
                'businesses_canonical_location_slug_unique'
            );
        });
    }
};