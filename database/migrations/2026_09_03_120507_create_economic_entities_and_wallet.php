<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('economic_entities', function (Blueprint $table) {
            $table->uuid('economic_entity_id')
                ->primary()
                ->default(DB::raw('gen_random_uuid()'))
            ;

            $table->string('entity_type', 32);

            $table->uuid('account_id')->nullable();

            $table->string('external_source', 64)->nullable();
            $table->string('external_entity_id', 128)->nullable();

            $table->timestampsTz();

            $table->foreign('account_id')
                ->references('account_id')
                ->on('accounts')
                ->restrictOnDelete();

            $table->unique('account_id');

            $table->unique(
                ['external_source', 'external_entity_id'],
                'economic_entities_external_unique'
            );

            $table->index('entity_type');
        });

        DB::statement("
            alter table economic_entities
            add constraint economic_entities_type_check
            check (
                entity_type in (
                    'user',
                    'business',
                    'club',
                    'application',
                    'platform'
                )
            )
        ");

        DB::statement("
            alter table economic_entities
            add constraint economic_entities_identity_check
            check (
                (
                    account_id is not null
                    and external_source is null
                    and external_entity_id is null
                )
                or
                (
                    account_id is null
                    and external_source is not null
                    and external_entity_id is not null
                )
            )
        ");

        Schema::create('wallets', function (Blueprint $table) {
            $table->uuid('wallet_id')
                ->primary()
                ->default(DB::raw('gen_random_uuid()'))
            ;

            $table->uuid('economic_entity_id');

            $table->string('asset_type', 8);
            $table->string('wallet_status', 32)->default('active');

            $table->timestampsTz();

            $table->foreign('economic_entity_id')
                ->references('economic_entity_id')
                ->on('economic_entities')
                ->restrictOnDelete();

            $table->unique(
                ['economic_entity_id', 'asset_type'],
                'wallets_entity_asset_unique'
            );

            $table->index('asset_type');
            $table->index('wallet_status');
        });

        DB::statement("
            alter table wallets
            add constraint wallets_asset_type_check
            check (
                asset_type in ('EP', 'BEP')
            )
        ");

        DB::statement("
            alter table wallets
            add constraint wallets_status_check
            check (
                wallet_status in (
                    'active',
                    'suspended',
                    'closed'
                )
            )
        ");

        DB::statement("
            create or replace function enforce_wallet_entity_rules()
            returns trigger
            language plpgsql
            as \$\$
            declare
                v_entity_type varchar(32);
                v_existing_wallets integer;
            begin
                select entity_type
                into v_entity_type
                from economic_entities
                where economic_entity_id = new.economic_entity_id;

                if v_entity_type is null then
                    raise exception 'Economic entity does not exist';
                end if;

                if v_entity_type = 'user' and new.asset_type <> 'EP' then
                    raise exception 'User entities may only hold EP wallets';
                end if;

                if v_entity_type = 'club' and new.asset_type <> 'EP' then
                    raise exception 'Club entities may only hold EP wallets';
                end if;

                if v_entity_type = 'application' and new.asset_type <> 'EP' then
                    raise exception 'Application entities may only hold EP wallets';
                end if;

                if v_entity_type = 'business' and new.asset_type <> 'BEP' then
                    raise exception 'Business entities may only hold BEP wallets';
                end if;

                if v_entity_type <> 'platform' then
                    select count(*)
                    into v_existing_wallets
                    from wallets
                    where economic_entity_id = new.economic_entity_id
                      and wallet_id <> new.wallet_id;

                    if v_existing_wallets > 0 then
                        raise exception 'Non-platform economic entities may only hold one wallet';
                    end if;
                end if;

                return new;
            end;
            \$\$
        ");

        DB::statement("
            create trigger trg_enforce_wallet_entity_rules
            before insert or update of economic_entity_id, asset_type
            on wallets
            for each row
            execute function enforce_wallet_entity_rules()
        ");
    }

    public function down(): void
    {
        DB::statement("
            drop trigger if exists trg_enforce_wallet_entity_rules
            on wallets
        ");

        DB::statement("
            drop function if exists enforce_wallet_entity_rules()
        ");

        Schema::dropIfExists('wallets');
        Schema::dropIfExists('economic_entities');
    }
};