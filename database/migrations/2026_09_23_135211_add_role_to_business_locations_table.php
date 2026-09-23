<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            create type business_location_role as enum (
                'branch',
                'head_office',
                'service_area'
            )
        ");

        Schema::table('business_locations', function (Blueprint $table) {
            $table->string('role')
                ->default('branch')
                ->after('slug');
        });

        DB::statement("
            alter table business_locations
            alter column role drop default
        ");

        DB::statement("
            alter table business_locations
            alter column role
            type business_location_role
            using role::business_location_role
        ");

        DB::statement("
            alter table business_locations
            alter column role
            set default 'branch'::business_location_role
        ");
    }

    public function down(): void
    {
        Schema::table('business_locations', function (Blueprint $table) {
            $table->dropColumn('role');
        });

        DB::statement(
            'drop type if exists business_location_role'
        );
    }
};