<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('
            ALTER TABLE public.accounts_users
            ADD CONSTRAINT accounts_users_auth_id_foreign
            FOREIGN KEY (auth_id)
            REFERENCES auth.users(id)
            ON DELETE CASCADE
        ');
    }

    public function down(): void
    {
        DB::statement('
            ALTER TABLE public.accounts_users
            DROP CONSTRAINT IF EXISTS accounts_users_auth_id_foreign
        ');
    }
};
