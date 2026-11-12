<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('signup_codes', function (Blueprint $table) {
            $table->id('signup_code_id');

            $table->text('code')->unique();

            $table->text('description')->nullable();

            $table->boolean('active')->default(true);

            $table->integer('max_uses')->nullable();

            $table->integer('use_count')->default(0);

            $table->timestampTz('expires_at')->nullable();

            $table->timestampTz('created_at')->useCurrent();

            $table->index('active');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('signup_codes');
    }
};
