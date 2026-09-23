<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('business_locations', function (Blueprint $table) {
            $table->string('slug')
                ->nullable()
                ->after('name');
        });

        /*
        |--------------------------------------------------------------------------
        | Backfill existing branch slugs
        |--------------------------------------------------------------------------
        |
        | Existing locations inherit the parent business slug.
        |
        */

        DB::statement('
            update business_locations bl
            set slug = b.slug
            from businesses b
            where b.id = bl.business_id
        ');

        Schema::table('business_locations', function (Blueprint $table) {
            $table->string('slug')
                ->nullable(false)
                ->change();

            $table->unique(
                ['canonical_location_id', 'slug'],
                'business_locations_canonical_location_slug_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('business_locations', function (Blueprint $table) {
            $table->dropUnique(
                'business_locations_canonical_location_slug_unique'
            );

            $table->dropColumn('slug');
        });
    }
};