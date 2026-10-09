<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Remove founding-member triggers/functions temporarily
        |--------------------------------------------------------------------------
        */

        DB::unprepared(<<<'SQL'
            drop trigger if exists sync_founding_member_status
            on public.accounts_users;

            drop function if exists public.sync_founding_member_status();

            drop trigger if exists prevent_founding_members_mutation
            on public.founding_members;

            drop function if exists public.prevent_founding_members_mutation();
        SQL);

        /*
        |--------------------------------------------------------------------------
        | Regenerate usernames from account_id
        |--------------------------------------------------------------------------
        |
        | Prefer the first 8 compact account-id characters.
        | If that collides, fall back to 10.
        |
        */

        DB::unprepared(<<<'SQL'
            do $$
            declare
                r record;
                v_username text;
            begin
                for r in
                    select account_id
                    from public.accounts_users
                    order by account_id
                loop
                    v_username :=
                        'user_' ||
                        substr(replace(r.account_id::text, '-', ''), 1, 8);

                    if exists (
                        select 1
                        from public.accounts_users
                        where username = v_username
                          and account_id <> r.account_id
                    ) then
                        v_username :=
                            'user_' ||
                            substr(replace(r.account_id::text, '-', ''), 1, 10);
                    end if;

                    if exists (
                        select 1
                        from public.accounts_users
                        where username = v_username
                          and account_id <> r.account_id
                    ) then
                        raise exception
                            'Unable to generate unique username for account %',
                            r.account_id;
                    end if;

                    update public.accounts_users
                    set username = v_username
                    where account_id = r.account_id;
                end loop;
            end;
            $$;
        SQL);

        /*
        |--------------------------------------------------------------------------
        | Rebuild founding_members around account_id
        |--------------------------------------------------------------------------
        */

        DB::unprepared(<<<'SQL'
            create table public.founding_members_new (
                account_id uuid primary key
                    references public.accounts(account_id)
                    on delete restrict
            );

            insert into public.founding_members_new (account_id)
            select au.account_id
            from public.founding_members fm
            join public.accounts_users au
                on au.auth_id = fm.auth_id;

            drop table public.founding_members;

            alter table public.founding_members_new
            rename to founding_members;
        SQL);

        /*
        |--------------------------------------------------------------------------
        | Restore founding-member derivation using account_id
        |--------------------------------------------------------------------------
        */

        DB::unprepared(<<<'SQL'
            create or replace function public.sync_founding_member_status()
            returns trigger
            language plpgsql
            set search_path = ''
            as $$
            begin
                NEW.is_founding_member := exists (
                    select 1
                    from public.founding_members fm
                    where fm.account_id = NEW.account_id
                );

                return NEW;
            end;
            $$;

            create trigger sync_founding_member_status
            before insert or update of account_id, is_founding_member
            on public.accounts_users
            for each row
            execute function public.sync_founding_member_status();
        SQL);

        /*
        |--------------------------------------------------------------------------
        | Lock the founding cohort again
        |--------------------------------------------------------------------------
        */

        DB::unprepared(<<<'SQL'
            create or replace function public.prevent_founding_members_mutation()
            returns trigger
            language plpgsql
            set search_path = ''
            as $$
            begin
                raise exception 'The founding member cohort is immutable';
            end;
            $$;

            create trigger prevent_founding_members_mutation
            before insert or update or delete
            on public.founding_members
            for each row
            execute function public.prevent_founding_members_mutation();
        SQL);

        /*
        |--------------------------------------------------------------------------
        | Replace user-account provisioning
        |--------------------------------------------------------------------------
        |
        | Auth ID remains the Supabase authentication identity.
        | Account ID becomes the Elsewhere platform identity.
        | Username is derived from account ID.
        |
        */

        DB::unprepared(<<<'SQL'
            create or replace function public.create_user_account(p_auth_id uuid)
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
                    'user_' ||
                    substr(replace(v_account_id::text, '-', ''), 1, 8);

                if exists (
                    select 1
                    from public.accounts_users
                    where username = v_username
                ) then
                    v_username :=
                        'user_' ||
                        substr(replace(v_account_id::text, '-', ''), 1, 10);
                end if;

                if exists (
                    select 1
                    from public.accounts_users
                    where username = v_username
                ) then
                    raise exception
                        'Unable to generate unique username for account %',
                        v_account_id;
                end if;

                insert into public.accounts (
                    account_id,
                    account_type,
                    created_at,
                    updated_at
                )
                values (
                    v_account_id,
                    'user',
                    now(),
                    now()
                );

                insert into public.accounts_users (
                    auth_id,
                    account_id,
                    username,
                    first_name,
                    last_name,
                    created_at,
                    updated_at
                )
                values (
                    p_auth_id,
                    v_account_id,
                    v_username,
                    null,
                    null,
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
        throw new RuntimeException(
            'This identity migration should not be automatically reversed.'
        );
    }
};
