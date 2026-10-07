<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Founding member flag
        |--------------------------------------------------------------------------
        |
        | This is stored on accounts_users because founding-member status belongs
        | to the human/user identity, rather than to the generic account itself.
        |
        */

        Schema::table('accounts_users', function (Blueprint $table) {
            $table->boolean('is_founding_member')
                ->default(false);
        });

        /*
        |--------------------------------------------------------------------------
        | Immutable founding cohort
        |--------------------------------------------------------------------------
        |
        | These auth UUIDs identify the original 16 users migrated from Perfect10
        | into Elsewhere on 7 October 2026.
        |
        | This table makes the founding cohort reproducible across fresh database
        | builds, testing environments and restores.
        |
        */

        Schema::create('founding_members', function (Blueprint $table) {
            $table->uuid('auth_id')->primary();
        });

        DB::table('founding_members')->insert([
            ['auth_id' => '7d969729-430a-4b6c-ad8b-91b0973026ec'],
            ['auth_id' => 'fbe94254-6ca3-4643-b0b8-68a06e871163'],
            ['auth_id' => '1575d6a2-d657-4f23-8922-fdd90726d8b6'],
            ['auth_id' => '832ec445-ddba-49fa-8e30-e218e9f42003'],
            ['auth_id' => 'a610cbde-01bc-4064-9b73-2d00f5a05ce9'],
            ['auth_id' => '31a9f1ce-2dcf-4d12-8985-950ba9161484'],
            ['auth_id' => '179f68da-3855-480e-9d66-0a892e3f44df'],
            ['auth_id' => '5b4aa78a-ce0b-4cc7-9311-dd452e16e149'],
            ['auth_id' => '7f02c6a3-f880-4b3a-bb92-0ea4fd54d624'],
            ['auth_id' => '038d9e5d-2331-4511-8e33-4bc3442b14e8'],
            ['auth_id' => '5e40cfd7-b0a0-47a7-9b6e-5d354fe679f7'],
            ['auth_id' => '6dfcfcc5-ff07-4d7a-aebf-c66873f2bba0'],
            ['auth_id' => 'bf9b3be0-10b0-47ab-8fbf-3b1b84001aa8'],
            ['auth_id' => '5a3caa7f-c4e9-45c8-adee-008b70fca08f'],
            ['auth_id' => '84acbd10-b2c5-43ab-86c0-fd16421f15f9'],
            ['auth_id' => '754341f7-6d47-42be-8e1b-d45880e1317f'],
        ]);

        /*
        |--------------------------------------------------------------------------
        | Set the flag for any founding users that already exist
        |--------------------------------------------------------------------------
        |
        | This handles the live database where the 16 users are already present.
        |
        */

        DB::statement(<<<'SQL'
            update public.accounts_users au
            set is_founding_member = true
            where exists (
                select 1
                from public.founding_members fm
                where fm.auth_id = au.auth_id
            )
        SQL);

        /*
        |--------------------------------------------------------------------------
        | Automatically derive founding-member status
        |--------------------------------------------------------------------------
        |
        | Every future accounts_users INSERT or auth_id change derives the flag
        | from the immutable founding_members registry.
        |
        | This means:
        |
        | - a founding UUID is always true
        | - every other UUID is always false
        | - application code cannot promote somebody manually
        |
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
                    where fm.auth_id = NEW.auth_id
                );

                return NEW;
            end;
            $$;

            create trigger sync_founding_member_status
            before insert or update of auth_id, is_founding_member
            on public.accounts_users
            for each row
            execute function public.sync_founding_member_status();
        SQL);

        /*
        |--------------------------------------------------------------------------
        | Lock the founding cohort
        |--------------------------------------------------------------------------
        |
        | Once this migration has established the 16 founding UUIDs, normal SQL
        | operations cannot add, remove or replace members of that cohort.
        |
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
    }

    public function down(): void
    {
        DB::unprepared(<<<'SQL'
            drop trigger if exists sync_founding_member_status
            on public.accounts_users;

            drop function if exists public.sync_founding_member_status();

            drop trigger if exists prevent_founding_members_mutation
            on public.founding_members;

            drop function if exists public.prevent_founding_members_mutation();
        SQL);

        Schema::dropIfExists('founding_members');

        Schema::table('accounts_users', function (Blueprint $table) {
            $table->dropColumn('is_founding_member');
        });
    }
};
