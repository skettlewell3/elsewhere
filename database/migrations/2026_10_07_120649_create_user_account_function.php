<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared(<<<'SQL'
            create or replace function public.create_user_account(
                p_auth_id uuid
            )
            returns uuid
            language plpgsql
            security definer
            set search_path = ''
            as $$
            declare
                v_account_id uuid;
                v_username text;
            begin
                v_account_id := gen_random_uuid();

                v_username :=
                    'user_'
                    || substr(
                        replace(v_account_id::text, '-', ''),
                        1,
                        8
                    );

                insert into public.accounts (
                    account_id,
                    account_type,
                    username,
                    created_at,
                    updated_at
                )
                values (
                    v_account_id,
                    'user',
                    v_username,
                    now(),
                    now()
                );

                insert into public.accounts_users (
                    auth_id,
                    account_id,
                    created_at,
                    updated_at
                )
                values (
                    p_auth_id,
                    v_account_id,
                    now(),
                    now()
                );

                return v_account_id;
            end;
            $$;
        SQL);
    }

    public function down(): void
    {
        DB::statement(
            'drop function if exists public.create_user_account(uuid)'
        );
    }
};
