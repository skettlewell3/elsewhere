<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('registration_attempts', function (Blueprint $table) {
            $table->id('registration_attempt_id');

            $table->foreignId('reservation_id')
                ->unique()
                ->constrained(
                    'signup_code_reservations',
                    'reservation_id'
                )
                ->restrictOnDelete();

            $table->uuid('auth_id')
                ->nullable()
                ->unique();

            $table->string('email', 255);

            $table->string('status', 32)
                ->default('reserved');

            $table->timestampTz('auth_requested_at')
                ->nullable();

            $table->timestampTz('auth_created_at')
                ->nullable();

            $table->timestampTz('provisioned_at')
                ->nullable();

            $table->timestampTz('completed_at')
                ->nullable();

            $table->timestampTz('failed_at')
                ->nullable();

            $table->string('last_error_code', 100)
                ->nullable();

            $table->timestampsTz();

            $table->index(
                ['status', 'updated_at'],
                'registration_attempts_status_idx'
            );

            $table->index(
                ['email', 'status'],
                'registration_attempts_email_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('registration_attempts');
    }
};
