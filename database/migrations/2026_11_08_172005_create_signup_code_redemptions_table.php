<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('signup_code_redemptions', function (Blueprint $table) {
            $table->id('redemption_id');

            $table->foreignId('signup_code_id')
                ->constrained('signup_codes', 'signup_code_id')
                ->restrictOnDelete();

            $table->uuid('auth_id')->unique();

            $table->timestampTz('redeemed_at')->useCurrent();

            $table->index('redeemed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('signup_code_redemptions');
    }
};
