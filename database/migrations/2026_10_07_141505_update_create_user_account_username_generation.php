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
                v_auth_compact text;
                v_username text;
            begin
                v_account_id := gen_random_uuid();

                v_auth_compact := replace(
                    p_auth_id::text,
                    '-',
                    ''
                );

                /*
                 * Standard platform username:
                 * user_ + first 8 characters of the central auth UUID.
                 */
                v_username :=
                    'user_'
                    || substr(v_auth_compact, 1, 8);

                /*
                 * Extremely unlikely collision fallback.
                 *
                 * Extend to 10 characters from the same auth UUID.
                 * This remains compatible with Perfect10's current
                 * 16-character display-name limit:
                 *
                 * user_ + 10 characters = 15 characters.
                 */
                if exists (
                    select 1
                    from public.accounts
                    where username = v_username
                ) then
                    v_username :=
                        'user_'
                        || substr(v_auth_compact, 1, 10);
                end if;

                /*
                 * A collision at 10 characters would indicate that the
                 * username scheme itself needs intervention rather than
                 * silently generating an inconsistent platform username.
                 */
                if exists (
                    select 1
                    from public.accounts
                    where username = v_username
                ) then
                    raise exception
                        'Unable to generate unique username for auth user %',
                        p_auth_id;
                end if;

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
};
