<?php

namespace Database\Seeders;

use App\Models\Country;
use Illuminate\Database\Seeder;

class CountrySeeder extends Seeder
{
    public function run(): void
    {
        Country::updateOrCreate(
            [
                'slug' => 'united-kingdom',
            ],
            [
                'name' => 'United Kingdom',
                'code' => 'GB',
                'default_locale' => 'en-GB',

                'latitude' => 54.5000000,
                'longitude' => -3.0000000,
                'map_zoom' => 5.20,
            ]
        );
    }
}