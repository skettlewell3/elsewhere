<?php

namespace App\Services\Economy\Protocols;

use App\Models\Economy\Wallet;
use App\Services\Economy\Ledger\LedgerService;
use App\Services\Economy\Permissions\TransferPermissionService;
use App\Services\Economy\Wallet\WalletBalanceService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class TransferProtocol
{
    public function __construct(
        private LedgerService $ledgerService,
        private WalletBalanceService $walletBalanceService,
        private TransferPermissionService $transferPermissionService
    ) {}

    public function execute(array $data)
    {
        return DB::transaction(function () use ($data) {
            $sourceWallet = Wallet::query()
                ->where('wallet_id', $data['source_wallet_id'])
                ->lockForUpdate()
                ->firstOrFail();

            $destinationWallet = Wallet::query()
                ->where('wallet_id', $data['destination_wallet_id'])
                ->firstOrFail();

            $this->transferPermissionService->assertAllowed(
                $sourceWallet,
                $destinationWallet
            );

            $balance = $this->walletBalanceService->getBalance(
                $sourceWallet
            );

            if ($balance < $data['amount']) {
                throw new RuntimeException(
                    'Insufficient wallet balance.'
                );
            }

            return $this->ledgerService->createTransfer(
                sourceWallet: $sourceWallet,
                destinationWallet: $destinationWallet,
                amount: $data['amount'],
                idempotencyKey: $data['idempotency_key']
            );
        });
    }
}