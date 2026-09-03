<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ledger_transactions', function (Blueprint $table) {
            $table->uuid('transaction_id')
                ->primary()
                ->default(DB::raw('gen_random_uuid()'))
            ;

            $table->string('transaction_type', 32);
            $table->string('transaction_status', 32)->default('pending');

            $table->uuid('initiated_by_entity_id');

            $table->string('source_application', 64)->nullable();
            $table->string('external_event_id', 128)->nullable();

            $table->uuid('reversal_of_transaction_id')->nullable();

            $table->jsonb('metadata')->nullable();

            $table->timestampTz('created_at')->useCurrent();
            $table->timestampTz('updated_at')->useCurrent();
            $table->timestampTz('completed_at')->nullable();

            $table->foreign('initiated_by_entity_id')
                ->references('economic_entity_id')
                ->on('economic_entities')
                ->restrictOnDelete();

            $table->unique(
                ['source_application', 'external_event_id'],
                'ledger_transactions_external_event_unique'
            );

            $table->index('transaction_type');
            $table->index('transaction_status');
            $table->index('initiated_by_entity_id');
            $table->index('reversal_of_transaction_id');
        });

        /*
         * Add the self-referencing FK after the table and its
         * transaction_id primary key have been fully created.
         */
        Schema::table('ledger_transactions', function (Blueprint $table) {
            $table->foreign('reversal_of_transaction_id')
                ->references('transaction_id')
                ->on('ledger_transactions')
                ->restrictOnDelete();
        });

        DB::statement("
            alter table ledger_transactions
            add constraint ledger_transactions_type_check
            check (
                transaction_type in (
                    'issuance',
                    'transfer',
                    'redemption',
                    'destruction',
                    'reversal',
                    'settlement'
                )
            )
        ");

        DB::statement("
            alter table ledger_transactions
            add constraint ledger_transactions_status_check
            check (
                transaction_status in (
                    'pending',
                    'completed',
                    'failed',
                    'cancelled'
                )
            )
        ");

        DB::statement("
            alter table ledger_transactions
            add constraint ledger_transactions_external_event_check
            check (
                (
                    source_application is null
                    and external_event_id is null
                )
                or
                (
                    source_application is not null
                    and external_event_id is not null
                )
            )
        ");

        DB::statement("
            alter table ledger_transactions
            add constraint ledger_transactions_completed_at_check
            check (
                (
                    transaction_status = 'completed'
                    and completed_at is not null
                )
                or
                (
                    transaction_status <> 'completed'
                    and completed_at is null
                )
            )
        ");

        Schema::create('ledger_entries', function (Blueprint $table) {
            $table->uuid('entry_id')
                ->primary()
                ->default(DB::raw('gen_random_uuid()'))
            ;

            $table->uuid('transaction_id');
            $table->uuid('wallet_id');

            $table->unsignedSmallInteger('entry_sequence');

            $table->string('entry_type', 32);

            $table->unsignedBigInteger('amount');

            $table->timestampTz('created_at')->useCurrent();

            $table->foreign('transaction_id')
                ->references('transaction_id')
                ->on('ledger_transactions')
                ->restrictOnDelete();

            $table->foreign('wallet_id')
                ->references('wallet_id')
                ->on('wallets')
                ->restrictOnDelete();

            $table->unique(
                ['transaction_id', 'entry_sequence'],
                'ledger_entries_transaction_sequence_unique'
            );

            $table->index('transaction_id');
            $table->index('wallet_id');
            $table->index('entry_type');
        });

        DB::statement("
            alter table ledger_entries
            add constraint ledger_entries_type_check
            check (
                entry_type in (
                    'debit',
                    'credit',
                    'issuance',
                    'destruction'
                )
            )
        ");

        DB::statement("
            alter table ledger_entries
            add constraint ledger_entries_amount_check
            check (
                amount > 0
            )
        ");

        DB::statement("
            create or replace function prevent_completed_ledger_transaction_mutation()
            returns trigger
            language plpgsql
            as \$\$
            begin
                if tg_op = 'DELETE' then
                    raise exception 'Ledger transactions cannot be deleted';
                end if;

                if old.transaction_status = 'completed' then
                    raise exception 'Completed ledger transactions are immutable';
                end if;

                return new;
            end;
            \$\$
        ");

        DB::statement("
            create trigger trg_prevent_completed_ledger_transaction_mutation
            before update or delete
            on ledger_transactions
            for each row
            execute function prevent_completed_ledger_transaction_mutation()
        ");

        DB::statement("
            create or replace function prevent_completed_ledger_entry_mutation()
            returns trigger
            language plpgsql
            as \$\$
            declare
                v_old_transaction_status varchar(32);
                v_new_transaction_status varchar(32);
            begin
                if tg_op = 'INSERT' then
                    select transaction_status
                    into v_new_transaction_status
                    from ledger_transactions
                    where transaction_id = new.transaction_id;

                    if v_new_transaction_status = 'completed' then
                        raise exception 'Entries cannot be added to completed ledger transactions';
                    end if;

                    return new;
                end if;

                if tg_op = 'DELETE' then
                    select transaction_status
                    into v_old_transaction_status
                    from ledger_transactions
                    where transaction_id = old.transaction_id;

                    if v_old_transaction_status = 'completed' then
                        raise exception 'Entries belonging to completed ledger transactions are immutable';
                    end if;

                    return old;
                end if;

                if tg_op = 'UPDATE' then
                    select transaction_status
                    into v_old_transaction_status
                    from ledger_transactions
                    where transaction_id = old.transaction_id;

                    if v_old_transaction_status = 'completed' then
                        raise exception 'Entries belonging to completed ledger transactions are immutable';
                    end if;

                    select transaction_status
                    into v_new_transaction_status
                    from ledger_transactions
                    where transaction_id = new.transaction_id;

                    if v_new_transaction_status = 'completed' then
                        raise exception 'Entries cannot be moved into completed ledger transactions';
                    end if;

                    return new;
                end if;

                return null;
            end;
            \$\$
        ");

        DB::statement("
            create trigger trg_prevent_completed_ledger_entry_mutation
            before insert or update or delete
            on ledger_entries
            for each row
            execute function prevent_completed_ledger_entry_mutation()
        ");
    }

    public function down(): void
    {
        DB::statement("
            drop trigger if exists trg_prevent_completed_ledger_entry_mutation
            on ledger_entries
        ");

        DB::statement("
            drop function if exists prevent_completed_ledger_entry_mutation()
        ");

        DB::statement("
            drop trigger if exists trg_prevent_completed_ledger_transaction_mutation
            on ledger_transactions
        ");

        DB::statement("
            drop function if exists prevent_completed_ledger_transaction_mutation()
        ");

        Schema::dropIfExists('ledger_entries');
        Schema::dropIfExists('ledger_transactions');
    }
};