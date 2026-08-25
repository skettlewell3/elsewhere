<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->dropColumn('display_name');
            $table->string('username', 32)->unique();
        });

        DB::statement("
            ALTER TABLE accounts
            ADD CONSTRAINT accounts_username_format
            CHECK (
                username ~ '^[a-z0-9_-]{3,32}$'
            )
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE accounts
            DROP CONSTRAINT IF EXISTS accounts_username_format
        ");

        Schema::table('accounts', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn('username');
            $table->string('display_name', 255);
        });
    }
};