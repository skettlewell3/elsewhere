<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_business_category', function (Blueprint $table) {

            $table->foreignId('business_id')
                ->constrained('businesses')
                ->cascadeOnDelete();

            $table->foreignId('business_category_id')
                ->constrained('business_categories')
                ->cascadeOnDelete();

            $table->primary([
                'business_id',
                'business_category_id',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'business_business_category'
        );
    }
};