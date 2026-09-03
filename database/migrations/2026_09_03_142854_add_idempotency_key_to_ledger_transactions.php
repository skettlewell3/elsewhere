<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ledger_transactions', function (Blueprint $table) {
            $table->uuid('idempotency_key')
                ->nullable()
                ->unique()
            ;
        });
    }

    public function down(): void
    {
        Schema::table('ledger_transactions', function (Blueprint $table) {
            $table->dropUnique(
                'ledger_transactions_idempotency_key_unique'
            );

            $table->dropColumn('idempotency_key');
        });
    }
};