<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('apps', function (Blueprint $table) {
            $table->id('app_id');

            $table->string('slug', 100)->unique();
            $table->string('name', 150);
            $table->string('category', 100);

            $table->text('description')->nullable();

            $table->string('image')->nullable();
            $table->string('alt')->nullable();

            $table->string('status', 30)
                ->default('coming_soon');

            $table->string('info_url')->nullable();
            $table->string('launch_url')->nullable();

            $table->unsignedInteger('sort_order')
                ->default(0);

            $table->timestamps();

            $table->index(['status', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('apps');
    }
};
