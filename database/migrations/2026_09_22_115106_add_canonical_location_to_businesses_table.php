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
            $table->unsignedBigInteger('canonical_location_id')
                ->nullable()
                ->after('location_id');

            $table->foreign('canonical_location_id')
                ->references('id')
                ->on('locations')
                ->restrictOnDelete();
        });

        DB::table('businesses')
            ->whereNotNull('location_id')
            ->update([
                'canonical_location_id' => DB::raw('location_id'),
            ]);

        Schema::table('businesses', function (Blueprint $table) {
            $table->unsignedBigInteger('canonical_location_id')
                ->nullable(false)
                ->change();

            $table->dropUnique('businesses_slug_unique');

            $table->unique(
                ['canonical_location_id', 'slug'],
                'businesses_canonical_location_slug_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropUnique('businesses_canonical_location_slug_unique');

            $table->unique('slug', 'businesses_slug_unique');

            $table->dropForeign(['canonical_location_id']);
            $table->dropColumn('canonical_location_id');
        });
    }
};