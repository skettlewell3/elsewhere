<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
            ALTER TABLE public.accounts_users
            ADD COLUMN display_name varchar(50)
        SQL);

        DB::statement(<<<'SQL'
            UPDATE public.accounts_users
            SET display_name = username
        SQL);

        DB::statement(<<<'SQL'
            CREATE FUNCTION public.set_account_user_default_display_name()
            RETURNS trigger
            LANGUAGE plpgsql
            SET search_path = ''
            AS $function$
            BEGIN
                IF NEW.display_name IS NULL THEN
                    NEW.display_name := NEW.username;
                END IF;

                RETURN NEW;
            END;
            $function$
        SQL);

        DB::statement(<<<'SQL'
            CREATE TRIGGER set_account_user_default_display_name
            BEFORE INSERT ON public.accounts_users
            FOR EACH ROW
            EXECUTE FUNCTION public.set_account_user_default_display_name()
        SQL);

        DB::statement(<<<'SQL'
            ALTER TABLE public.accounts_users
            ALTER COLUMN display_name SET NOT NULL
        SQL);

        DB::statement(<<<'SQL'
            CREATE UNIQUE INDEX accounts_users_display_name_unique
            ON public.accounts_users (lower(display_name))
        SQL);
    }

    public function down(): void
    {
        DB::statement(<<<'SQL'
            DROP TRIGGER IF EXISTS
                set_account_user_default_display_name
            ON public.accounts_users
        SQL);

        DB::statement(<<<'SQL'
            DROP FUNCTION IF EXISTS
                public.set_account_user_default_display_name()
        SQL);

        DB::statement(<<<'SQL'
            DROP INDEX IF EXISTS
                public.accounts_users_display_name_unique
        SQL);

        DB::statement(<<<'SQL'
            ALTER TABLE public.accounts_users
            DROP COLUMN IF EXISTS display_name
        SQL);
    }
};
