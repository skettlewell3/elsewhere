<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\Location;
use Illuminate\Database\Seeder;

class BusinessSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $businesses = [
            [
                'name' => 'Harbour Coffee Co.',
                'slug' => 'harbour-coffee-co',
                'description' => 'Independent coffee shop near Southampton city centre.',
                'website_url' => 'https://example.com',
                'is_active' => true,
                'offers_ep_redemption' => true,

                'locations' => [
                    [
                        'location_slug' => 'southampton-city-centre',
                        'name' => 'Southampton City Centre',
                        'latitude' => 50.8985,
                        'longitude' => -1.4044,
                        'is_primary' => true,
                        'is_mobile' => false,
                    ],
                ],
            ],

            [
                'name' => 'South Coast Cycles',
                'slug' => 'south-coast-cycles',
                'description' => 'Independent bicycle shop serving Southampton and surrounding areas.',
                'website_url' => 'https://example.com',
                'is_active' => true,
                'offers_ep_redemption' => true,

                'locations' => [
                    [
                        'location_slug' => 'portswood',
                        'name' => 'Portswood',
                        'latitude' => 50.9120,
                        'longitude' => -1.4000,
                        'is_primary' => true,
                        'is_mobile' => false,
                    ],
                ],
            ],

            [
                'name' => 'Oak & Stone Interiors',
                'slug' => 'oak-and-stone-interiors',
                'description' => 'Independent interiors and home furnishings business.',
                'website_url' => 'https://example.com',
                'is_active' => true,
                'offers_ep_redemption' => false,

                'locations' => [
                    [
                        'location_slug' => 'southampton-city-centre',
                        'name' => 'Southampton',
                        'latitude' => 50.9270,
                        'longitude' => -1.3730,
                        'is_primary' => true,
                        'is_mobile' => false,
                    ],
                    [
                        'location_slug' => 'leeds-city-centre',
                        'name' => 'Leeds',
                        'latitude' => 53.8008,
                        'longitude' => -1.5491,
                        'is_primary' => false,
                        'is_mobile' => false,
                    ],
                ],
            ],

            [
                'name' => 'Solent Mobile Repairs',
                'slug' => 'solent-mobile-repairs',
                'description' => 'Mobile device repair service covering Southampton and surrounding areas.',
                'website_url' => 'https://example.com',
                'is_active' => true,
                'offers_ep_redemption' => false,

                'locations' => [
                    [
                        'location_slug' => 'southampton',
                        'name' => 'Southampton',
                        'latitude' => null,
                        'longitude' => null,
                        'is_primary' => true,
                        'is_mobile' => true,
                    ],
                ],
            ],

            [
                'name' => 'The Green Grocer',
                'slug' => 'the-green-grocer',
                'description' => 'Independent local grocer specialising in fresh produce.',
                'website_url' => 'https://example.com',
                'is_active' => true,
                'offers_ep_redemption' => true,

                'locations' => [
                    [
                        'location_slug' => 'portswood',
                        'name' => 'Portswood',
                        'latitude' => 50.9055,
                        'longitude' => -1.3905,
                        'is_primary' => true,
                        'is_mobile' => false,
                    ],
                ],
            ],

            [
                'name' => 'Solent Coffee Roasters',
                'slug' => 'solent-coffee-roasters',
                'description' => 'Small-batch coffee roastery supplying cafes and local customers.',
                'website_url' => 'https://example.com',
                'is_active' => true,
                'offers_ep_redemption' => false,

                'locations' => [
                    [
                        'location_slug' => 'southampton-city-centre',
                        'name' => 'Southampton Central',
                        'slug' => 'solent-coffee-roasters',
                        'latitude' => 50.9142,
                        'longitude' => -1.3971,
                        'is_primary' => true,
                        'is_mobile' => false,
                    ],
                    [
                        'location_slug' => 'portswood',
                        'name' => 'Portswood',
                        'latitude' => 50.9262,
                        'longitude' => -1.3948,
                        'is_primary' => false,
                        'is_mobile' => false,
                    ],
                ],
            ],

            [
                'name' => 'Southampton Print Studio',
                'slug' => 'southampton-print-studio',
                'description' => 'Local print and design studio offering commercial and personal printing.',
                'website_url' => 'https://example.com',
                'is_active' => true,
                'offers_ep_redemption' => false,

                'locations' => [
                    [
                        'location_slug' => 'southampton-city-centre',
                        'name' => 'Southampton City Centre',
                        'latitude' => 50.9028,
                        'longitude' => -1.3954,
                        'is_primary' => true,
                        'is_mobile' => false,
                    ],
                ],
            ],

            [
                'name' => 'Harbour Books',
                'slug' => 'harbour-books',
                'description' => 'Independent bookshop specialising in new and second-hand books.',
                'website_url' => 'https://example.com',
                'is_active' => true,
                'offers_ep_redemption' => false,

                'locations' => [
                    [
                        'location_slug' => 'southampton-city-centre',
                        'name' => 'Southampton City Centre',
                        'latitude' => 50.8989,
                        'longitude' => -1.4008,
                        'is_primary' => true,
                        'is_mobile' => false,
                    ],
                ],
            ],

            [
                'name' => 'Solent Fitness',
                'slug' => 'solent-fitness',
                'description' => 'Independent fitness studio offering classes, personal training and coaching.',
                'website_url' => 'https://example.com',
                'is_active' => true,
                'offers_ep_redemption' => true,

                'locations' => [
                    [
                        'location_slug' => 'portswood',
                        'name' => 'Portswood',
                        'latitude' => 50.9071,
                        'longitude' => -1.3862,
                        'is_primary' => true,
                        'is_mobile' => false,
                    ],
                ],
            ],

            [
                'name' => 'Southampton Tech Repairs',
                'slug' => 'southampton-tech-repairs',
                'description' => 'Local repair service for laptops, phones, tablets and other devices.',
                'website_url' => 'https://example.com',
                'is_active' => true,
                'offers_ep_redemption' => false,

                'locations' => [
                    [
                        'location_slug' => 'southampton-city-centre',
                        'name' => 'Southampton City Centre',
                        'latitude' => 50.8958,
                        'longitude' => -1.4091,
                        'is_primary' => true,
                        'is_mobile' => false,
                    ],
                ],
            ],

            [
                'name' => 'The Kitchen Garden',
                'slug' => 'the-kitchen-garden',
                'description' => 'Independent kitchenware and home goods shop.',
                'website_url' => 'https://example.com',
                'is_active' => true,
                'offers_ep_redemption' => false,

                'locations' => [
                    [
                        'location_slug' => 'portswood',
                        'name' => 'Portswood',
                        'latitude' => 50.9036,
                        'longitude' => -1.4147,
                        'is_primary' => true,
                        'is_mobile' => false,
                    ],
                ],
            ],

            [
                'name' => 'Solent Design Works',
                'slug' => 'solent-design-works',
                'description' => 'Freelance graphic design and branding studio serving local businesses.',
                'website_url' => 'https://example.com',
                'is_active' => true,
                'offers_ep_redemption' => false,

                'locations' => [
                    [
                        'location_slug' => 'southampton-city-centre',
                        'name' => 'Southampton City Centre',
                        'latitude' => 50.9184,
                        'longitude' => -1.3895,
                        'is_primary' => true,
                        'is_mobile' => false,
                    ],
                ],
            ],

            [
                'name' => 'South Coast Florists',
                'slug' => 'south-coast-florists',
                'description' => 'Independent florist creating bouquets and arrangements for local customers.',
                'website_url' => 'https://example.com',
                'is_active' => true,
                'offers_ep_redemption' => false,

                'locations' => [
                    [
                        'location_slug' => 'portswood',
                        'name' => 'Portswood',
                        'latitude' => 50.9113,
                        'longitude' => -1.4102,
                        'is_primary' => true,
                        'is_mobile' => false,
                    ],
                ],
            ],

            [
                'name' => 'Dockside Deli',
                'slug' => 'dockside-deli',
                'description' => 'Independent deli serving sandwiches, salads, pastries and local produce.',
                'website_url' => 'https://example.com',
                'is_active' => true,
                'offers_ep_redemption' => true,

                'locations' => [
                    [
                        'location_slug' => 'southampton-city-centre',
                        'name' => 'Southampton City Centre',
                        'latitude' => 50.8927,
                        'longitude' => -1.3976,
                        'is_primary' => true,
                        'is_mobile' => false,
                    ],
                ],
            ],

            [
                'name' => 'Southampton Web Studio',
                'slug' => 'southampton-web-studio',
                'description' => 'Independent web development studio building websites and digital products.',
                'website_url' => 'https://example.com',
                'is_active' => true,
                'offers_ep_redemption' => false,

                'locations' => [
                    [
                        'location_slug' => 'portswood',
                        'name' => 'Portswood',
                        'latitude' => 50.9211,
                        'longitude' => -1.4028,
                        'is_primary' => true,
                        'is_mobile' => false,
                    ],
                ],
            ],

            [
                'name' => 'Solent Garden Services',
                'slug' => 'solent-garden-services',
                'description' => 'Mobile gardening and garden maintenance service covering Southampton and surrounding areas.',
                'website_url' => 'https://example.com',
                'is_active' => true,
                'offers_ep_redemption' => false,

                'locations' => [
                    [
                        'location_slug' => 'southampton',
                        'name' => 'Southampton',
                        'latitude' => null,
                        'longitude' => null,
                        'is_primary' => true,
                        'is_mobile' => true,
                    ],
                ],
            ],
        ];

        foreach ($businesses as $data) {
            $business = Business::updateOrCreate(
                [
                    'slug' => $data['slug'],
                ],
                [
                    'name' => $data['name'],
                    'description' => $data['description'],
                    'website_url' => $data['website_url'],
                    'is_active' => $data['is_active'],
                    'offers_ep_redemption' => $data['offers_ep_redemption'],
                ]
            );

            foreach ($data['locations'] as $locationData) {
                $location = Location::where(
                    'slug',
                    $locationData['location_slug']
                )->firstOrFail();

                $businessLocation = $business->locations()->firstOrNew([
                    'location_id' => $location->id,
                    'name' => $locationData['name'],
                ]);

                $businessLocation->fill([
                    'location_id' => $location->id,
                    'name' => $locationData['name'],

                    'address_line_1' => null,
                    'address_line_2' => null,
                    'postcode' => null,

                    'latitude' => $locationData['latitude'],
                    'longitude' => $locationData['longitude'],

                    'phone' => null,
                    'email' => null,

                    'is_primary' => $locationData['is_primary'],
                    'is_mobile' => $locationData['is_mobile'],
                    'is_active' => true,
                ]);

                /*
                |--------------------------------------------------------------------------
                | Explicit branch slug
                |--------------------------------------------------------------------------
                |
                | If the seed data specifies one, use it.
                |
                | Otherwise a NEW BusinessLocation is left without a slug so the
                | BusinessLocationSlugService can generate it automatically.
                |
                | Existing locations keep their current slug.
                |
                */

                if (array_key_exists('slug', $locationData)) {
                    $businessLocation->slug = $locationData['slug'];
                }

                $businessLocation->save();
            }
        }
    }
}