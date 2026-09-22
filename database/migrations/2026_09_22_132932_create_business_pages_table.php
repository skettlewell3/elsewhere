<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_pages', function (Blueprint $table) {
            $table->id();

            $table->foreignId('business_id')
                ->unique()
                ->constrained('businesses')
                ->cascadeOnDelete();

            $table->string('headline')->nullable();

            $table->text('short_description')->nullable();

            $table->text('about')->nullable();

            $table->boolean('is_published')
                ->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_pages');
    }
};