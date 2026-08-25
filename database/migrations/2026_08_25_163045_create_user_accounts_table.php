<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_accounts', function (Blueprint $table) {
            $table->uuid('auth_id')->primary();

            $table->foreignUuid('account_id')
                ->constrained('accounts', 'account_id')
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique('account_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_accounts');
    }
};