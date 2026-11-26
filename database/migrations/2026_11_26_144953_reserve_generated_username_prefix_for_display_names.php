
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
            ALTER TABLE public.accounts_users
            ADD CONSTRAINT accounts_users_display_name_reserved_prefix
            CHECK (
                display_name = username
                OR display_name !~* '^user_'
            )
        SQL);
    }

    public function down(): void
    {
        DB::statement(<<<'SQL'
            ALTER TABLE public.accounts_users
            DROP CONSTRAINT IF EXISTS
                accounts_users_display_name_reserved_prefix
        SQL);
    }
};
