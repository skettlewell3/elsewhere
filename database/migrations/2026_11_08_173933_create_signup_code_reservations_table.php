<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('signup_code_reservations', function (Blueprint $table) {
            $table->id('reservation_id');

            $table->foreignId('signup_code_id')
                ->constrained('signup_codes', 'signup_code_id')
                ->restrictOnDelete();

            $table->string('token_hash', 64)->unique();

            $table->string('email', 255);

            $table->string('status', 16)->default('reserved');

            $table->timestampTz('expires_at');

            $table->timestampTz('created_at')->useCurrent();

            $table->timestampTz('released_at')->nullable();

            $table->index(
                ['signup_code_id', 'status', 'expires_at'],
                'signup_reservations_capacity_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('signup_code_reservations');
    }
};
