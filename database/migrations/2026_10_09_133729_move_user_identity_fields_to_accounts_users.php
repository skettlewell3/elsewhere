<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts_users', function (Blueprint $table) {
            $table->string('username', 32)->nullable()->unique();
            $table->string('first_name', 100)->nullable();
            $table->string('last_name', 100)->nullable();
        });

        DB::statement(<<<'SQL'
            update public.accounts_users au
            set username = a.username
            from public.accounts a
            where a.account_id = au.account_id
        SQL);

        Schema::table('accounts_users', function (Blueprint $table) {
            $table->string('username', 32)->nullable(false)->change();
        });

        Schema::table('accounts', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn('username');
        });
    }

    public function down(): void
    {
        Schema::table('accounts', function (Blueprint $table) {
            $table->string('username', 32)->nullable()->unique();
        });

        DB::statement(<<<'SQL'
            update public.accounts a
            set username = au.username
            from public.accounts_users au
            where au.account_id = a.account_id
        SQL);

        Schema::table('accounts', function (Blueprint $table) {
            $table->string('username', 32)->nullable(false)->change();
        });

        Schema::table('accounts_users', function (Blueprint $table) {
            $table->dropUnique(['username']);
            $table->dropColumn([
                'username',
                'first_name',
                'last_name',
            ]);
        });
    }
};
