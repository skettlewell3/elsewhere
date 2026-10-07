<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
            create or replace function public.handle_new_auth_user()
            returns trigger
            language plpgsql
            security definer
            set search_path = ''
            as $$
            begin
                perform public.create_user_account(new.id);

                return new;
            end;
            $$;
        SQL);

        DB::unprepared(<<<'SQL'
            drop trigger if exists on_auth_user_created
            on auth.users;

            create trigger on_auth_user_created
            after insert on auth.users
            for each row
            execute function public.handle_new_auth_user();
        SQL);
    }

    public function down(): void
    {
        DB::unprepared(<<<'SQL'
            drop trigger if exists on_auth_user_created
            on auth.users;
        SQL);

        DB::statement(
            'drop function if exists public.handle_new_auth_user()'
        );
    }
};
