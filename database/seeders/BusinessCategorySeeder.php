<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\BusinessCategory;
use Illuminate\Database\Seeder;

class BusinessCategorySeeder extends Seeder
{
    public function run(): void
    {
        $foodDrink = BusinessCategory::updateOrCreate(
            ['slug' => 'food-drink'],
            [
                'name' => 'Food & Drink',
                'colour_key' => 'food-drink',
                'sort_order' => 10,
                'is_active' => true,
            ]
        );

        $shopping = BusinessCategory::updateOrCreate(
            ['slug' => 'shopping'],
            [
                'name' => 'Shopping',
                'colour_key' => 'shopping',
                'sort_order' => 20,
                'is_active' => true,
            ]
        );

        $services = BusinessCategory::updateOrCreate(
            ['slug' => 'services'],
            [
                'name' => 'Services',
                'colour_key' => 'services',
                'sort_order' => 30,
                'is_active' => true,
            ]
        );


        /*
         * FOOD & DRINK
         */

        $this->syncCategory(
            'harbour-coffee-co',
            $foodDrink
        );

        $this->syncCategory(
            'solent-coffee-roasters',
            $foodDrink
        );

        $this->syncCategory(
            'the-green-grocer',
            $foodDrink
        );

        $this->syncCategory(
            'the-kitchen-garden',
            $foodDrink
        );

        $this->syncCategory(
            'dockside-deli',
            $foodDrink
        );


        /*
         * SHOPPING
         */

        $this->syncCategory(
            'south-coast-cycles',
            $shopping
        );

        $this->syncCategory(
            'oak-and-stone-interiors',
            $shopping
        );

        $this->syncCategory(
            'harbour-books',
            $shopping
        );

        $this->syncCategory(
            'south-coast-florists',
            $shopping
        );


        /*
         * SERVICES
         */

        $this->syncCategory(
            'solent-mobile-repairs',
            $services
        );

        $this->syncCategory(
            'southampton-print-studio',
            $services
        );

        $this->syncCategory(
            'solent-fitness',
            $services
        );

        $this->syncCategory(
            'southampton-tech-repairs',
            $services
        );

        $this->syncCategory(
            'solent-design-works',
            $services
        );

        $this->syncCategory(
            'southampton-web-studio',
            $services
        );

        $this->syncCategory(
            'solent-garden-services',
            $services
        );
    }


    private function syncCategory(
        string $businessSlug,
        BusinessCategory $category
    ): void {
        $business = Business::where(
            'slug',
            $businessSlug
        )->firstOrFail();

        $business
            ->categories()
            ->syncWithoutDetaching([
                $category->id,
            ]);
    }
}