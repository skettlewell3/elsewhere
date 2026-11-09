<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('signup_code_redemptions', function (Blueprint $table) {
            $table->foreignId('reservation_id')
                ->nullable()
                ->unique()
                ->constrained(
                    'signup_code_reservations',
                    'reservation_id'
                )
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('signup_code_redemptions', function (Blueprint $table) {
            $table->dropForeign(['reservation_id']);
            $table->dropUnique(['reservation_id']);
            $table->dropColumn('reservation_id');
        });
    }
};
