<?php

namespace Database\Seeders;

use App\Models\App;
use Illuminate\Database\Seeder;

class AppSeeder extends Seeder
{
    public function run(): void
    {
        App::query()->updateOrCreate(
            [
                'slug' => 'perfect10',
            ],
            [
                'name' => 'Perfect10',
                'category' => 'Sports',

                'description' =>
                    'Football prediction game, play with and against friends, join clubs, compete together and win prizes!',

                'image' =>
                    'images/apps/p10/p10Transparent.png',

                'alt' => 'Perfect10',

                'status' => 'active',

                'info_url' => '/apps/perfect10',

                'launch_url' =>
                    'https://perfect10.elsewhere.uk.com',

                'sort_order' => 1,
            ]
        );
    }
}
