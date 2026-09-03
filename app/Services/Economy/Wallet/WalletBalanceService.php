<?php

namespace App\Services\Economy\Wallet;

use App\Models\Economy\Wallet;
use Illuminate\Support\Facades\DB;

class WalletBalanceService
{
    public function getBalance(Wallet $wallet): int
    {
        return (int) DB::table('ledger_entries')
            ->join(
                'ledger_transactions',
                'ledger_transactions.transaction_id',
                '=',
                'ledger_entries.transaction_id'
            )
            ->where(
                'ledger_entries.wallet_id',
                $wallet->wallet_id
            )
            ->where(
                'ledger_transactions.transaction_status',
                'completed'
            )
            ->selectRaw("
                coalesce(
                    sum(
                        case
                            when ledger_entries.entry_type in ('credit', 'issuance')
                                then ledger_entries.amount
                            when ledger_entries.entry_type in ('debit', 'destruction')
                                then -ledger_entries.amount
                            else 0
                        end
                    ),
                    0
                ) as balance
            ")
            ->value('balance');
    }
}