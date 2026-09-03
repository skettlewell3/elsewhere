<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            alter table ledger_entries
            add constraint ledger_entries_sequence_check
            check (
                entry_sequence > 0
            )
        ");
    }

    public function down(): void
    {
        DB::statement("
            alter table ledger_entries
            drop constraint if exists ledger_entries_sequence_check
        ");
    }
};