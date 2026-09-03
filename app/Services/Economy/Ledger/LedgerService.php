<?php

namespace App\Services\Economy\Ledger;

use App\Models\Economy\LedgerEntry;
use App\Models\Economy\LedgerTransaction;
use App\Models\Economy\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class LedgerService
{
    public function createTransfer(
        Wallet $sourceWallet,
        Wallet $destinationWallet,
        int $amount,
        string $idempotencyKey
    ): LedgerTransaction {
        $existing = LedgerTransaction::query()
            ->where('idempotency_key', $idempotencyKey)
            ->first();

        if ($existing) {
            return $this->resolveExistingTransfer(
                transaction: $existing,
                sourceWallet: $sourceWallet,
                destinationWallet: $destinationWallet,
                amount: $amount
            );
        }

        $transactionId = (string) Str::uuid();

        $inserted = DB::table('ledger_transactions')
            ->insertOrIgnore([
                'transaction_id' => $transactionId,
                'transaction_type' => 'transfer',
                'transaction_status' => 'pending',
                'initiated_by_entity_id' =>
                    $sourceWallet->economic_entity_id,
                'idempotency_key' => $idempotencyKey,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        /*
         * If another request claimed this idempotency key first,
         * PostgreSQL will reject this insert without raising an
         * error because insertOrIgnore uses ON CONFLICT.
         */
        if ($inserted === 0) {
            $existing = LedgerTransaction::query()
                ->where('idempotency_key', $idempotencyKey)
                ->firstOrFail();

            return $this->resolveExistingTransfer(
                transaction: $existing,
                sourceWallet: $sourceWallet,
                destinationWallet: $destinationWallet,
                amount: $amount
            );
        }

        $transaction = LedgerTransaction::findOrFail(
            $transactionId
        );

        LedgerEntry::create([
            'transaction_id' => $transaction->transaction_id,
            'wallet_id' => $sourceWallet->wallet_id,
            'entry_sequence' => 1,
            'entry_type' => 'debit',
            'amount' => $amount,
        ]);

        LedgerEntry::create([
            'transaction_id' => $transaction->transaction_id,
            'wallet_id' => $destinationWallet->wallet_id,
            'entry_sequence' => 2,
            'entry_type' => 'credit',
            'amount' => $amount,
        ]);

        $transaction->update([
            'transaction_status' => 'completed',
            'completed_at' => now(),
        ]);

        return $transaction->fresh([
            'entries',
        ]);
    }

    private function resolveExistingTransfer(
        LedgerTransaction $transaction,
        Wallet $sourceWallet,
        Wallet $destinationWallet,
        int $amount
    ): LedgerTransaction {
        if ($transaction->transaction_type !== 'transfer') {
            throw new RuntimeException(
                'Idempotency key has already been used for another transaction type.'
            );
        }

        $transaction->load('entries');

        $debit = $transaction->entries
            ->firstWhere('entry_type', 'debit');

        $credit = $transaction->entries
            ->firstWhere('entry_type', 'credit');

        if (
            !$debit ||
            !$credit ||
            $debit->wallet_id !== $sourceWallet->wallet_id ||
            $credit->wallet_id !== $destinationWallet->wallet_id ||
            $debit->amount !== $amount ||
            $credit->amount !== $amount
        ) {
            throw new RuntimeException(
                'Idempotency key has already been used for a different transfer.'
            );
        }

        return $transaction;
    }
}