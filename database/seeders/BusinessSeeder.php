<?php

namespace Database\Seeders;

use App\Models\Business;
use Illuminate\Database\Seeder;

class BusinessSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Business::create([
            'name' => 'Harbour Coffee Co.',
            'slug' => 'harbour-coffee-co',
            'description' => 'Independent coffee shop near Southampton city centre.',
            'latitude' => 50.8985,
            'longitude' => -1.4044,
            'website_url' => 'https://example.com',
            'is_active' => true,
        ]);

        Business::create([
            'name' => 'South Coast Cycles',
            'slug' => 'south-coast-cycles',
            'description' => 'Independent bicycle shop serving Southampton and surrounding areas.',
            'latitude' => 50.9120,
            'longitude' => -1.4000,
            'website_url' => 'https://example.com',
            'is_active' => true,
        ]);

        Business::create([
            'name' => 'Oak & Stone Interiors',
            'slug' => 'oak-and-stone-interiors',
            'description' => 'Independent interiors and home furnishings business.',
            'latitude' => 50.9270,
            'longitude' => -1.3730,
            'website_url' => 'https://example.com',
            'is_active' => true,
        ]);

        Business::create([
            'name' => 'Solent Mobile Repairs',
            'slug' => 'solent-mobile-repairs',
            'description' => 'Mobile device repair service covering Southampton and surrounding areas.',
            'latitude' => 50.8915,
            'longitude' => -1.3760,
            'website_url' => 'https://example.com',
            'is_active' => true,
        ]);

        Business::create([
            'name' => 'The Green Grocer',
            'slug' => 'the-green-grocer',
            'description' => 'Independent local grocer specialising in fresh produce.',
            'latitude' => 50.9055,
            'longitude' => -1.3905,
            'website_url' => 'https://example.com',
            'is_active' => true,
        ]);
    }
}