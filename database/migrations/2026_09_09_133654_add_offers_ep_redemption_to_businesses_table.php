<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {

            $table->boolean('offers_ep_redemption')
                ->default(false)
                ->after('is_active');

        });
    }

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {

            $table->dropColumn(
                'offers_ep_redemption'
            );

        });
    }
};