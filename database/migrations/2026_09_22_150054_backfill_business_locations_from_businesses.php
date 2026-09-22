<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('businesses')
            ->orderBy('id')
            ->get()
            ->each(function ($business) {
                DB::table('business_locations')->insert([
                    'business_id' => $business->id,
                    'location_id' => $business->location_id,
                    'canonical_location_id' => $business->canonical_location_id,

                    'name' => null,

                    'address_line_1' => null,
                    'address_line_2' => null,
                    'postcode' => null,

                    'latitude' => $business->latitude,
                    'longitude' => $business->longitude,

                    'phone' => null,
                    'email' => null,

                    'is_primary' => true,
                    'is_mobile' => false,
                    'is_active' => $business->is_active,

                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });
    }

    public function down(): void
    {
        DB::table('business_locations')->delete();
    }
};