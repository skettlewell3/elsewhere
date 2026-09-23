<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\Location;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    public function run(): void
    {
        $uk = Country::where('slug', 'united-kingdom')
            ->firstOrFail();

        $england = Location::updateOrCreate(
            [
                'slug' => 'england',
                'parent_id' => null,
            ],
            [
                'name' => 'England',
                'type' => 'nation',
                'country_id' => $uk->id,
                'latitude' => 52.8000000,
                'longitude' => -1.5000000,
                'map_zoom' => 5.80,
            ]
        );

        $westYorkshire = Location::updateOrCreate(
            [
                'slug' => 'west-yorkshire',
                'parent_id' => $england->id,
            ],
            [
                'name' => 'West Yorkshire',
                'type' => 'county',
                'country_id' => $uk->id,
                'latitude' => 53.8000000,
                'longitude' => -1.6500000,
                'map_zoom' => 8.50,
            ]
        );

        $leeds = Location::updateOrCreate(
            [
                'slug' => 'leeds',
                'parent_id' => $westYorkshire->id,
            ],
            [
                'name' => 'Leeds',
                'type' => 'city',
                'country_id' => $uk->id,
                'latitude' => 53.8008000,
                'longitude' => -1.5491000,
                'map_zoom' => 10.50,
            ]
        );

        Location::updateOrCreate(
            [
                'slug' => 'morley',
                'parent_id' => $leeds->id,
            ],
            [
                'name' => 'Morley',
                'type' => 'town',
                'country_id' => $uk->id,
                'latitude' => 53.7492000,
                'longitude' => -1.6037000,
            ]
        );

        Location::updateOrCreate(
            [
                'slug' => 'leeds-city-centre',
                'parent_id' => $leeds->id,
            ],
            [
                'name' => 'Leeds City Centre',
                'type' => 'district',
                'country_id' => $uk->id,
                'latitude' => 53.8008000,
                'longitude' => -1.5491000,
            ]
        );

        $hampshire = Location::updateOrCreate(
            [
                'slug' => 'hampshire',
                'parent_id' => $england->id,
            ],
            [
                'name' => 'Hampshire',
                'type' => 'county',
                'country_id' => $uk->id,
                'latitude' => 51.0500000,
                'longitude' => -1.2500000,
                'map_zoom' => 8.50,
            ]
        );

        $southampton = Location::updateOrCreate(
            [
                'slug' => 'southampton',
                'parent_id' => $hampshire->id,
            ],
            [
                'name' => 'Southampton',
                'type' => 'city',
                'country_id' => $uk->id,
                'latitude' => 50.9097000,
                'longitude' => -1.4044000,
                'map_zoom' => 11.00,
            ]
        );

        Location::updateOrCreate(
            [
                'slug' => 'southampton-city-centre',
                'parent_id' => $southampton->id,
            ],
            [
                'name' => 'Southampton City Centre',
                'type' => 'district',
                'country_id' => $uk->id,
                'latitude' => 50.9050,
                'longitude' => -1.4040,
            ]
        );

        Location::updateOrCreate(
            [
                'slug' => 'portswood',
                'parent_id' => $southampton->id,
            ],
            [
                'name' => 'Portswood',
                'type' => 'district',
                'country_id' => $uk->id,
                'latitude' => 50.9270,
                'longitude' => -1.3950,
            ]
        );
    }
}