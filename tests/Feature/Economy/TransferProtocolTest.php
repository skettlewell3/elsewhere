<?php

namespace Tests\Feature\Economy;

use App\Models\Economy\EconomicEntity;
use App\Models\Economy\LedgerEntry;
use App\Models\Economy\LedgerTransaction;
use App\Models\Economy\Wallet;
use App\Services\Economy\Protocols\TransferProtocol;
use App\Services\Economy\Wallet\WalletBalanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class TransferProtocolTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_transfer_ep_to_club(): void
    {
        [$userEntity, $userWallet] = $this->createEntityWithWallet(
            entityType: 'user',
            assetType: 'EP',
            externalSource: 'test',
            externalEntityId: 'user-1'
        );

        [, $clubWallet] = $this->createEntityWithWallet(
            entityType: 'club',
            assetType: 'EP',
            externalSource: 'perfect10',
            externalEntityId: 'club-1'
        );

        $this->issueToWallet(
            wallet: $userWallet,
            initiator: $userEntity,
            amount: 100
        );

        $protocol = app(TransferProtocol::class);

        $transaction = $protocol->execute([
            'source_wallet_id' => $userWallet->wallet_id,
            'destination_wallet_id' => $clubWallet->wallet_id,
            'amount' => 40,
            'idempotency_key' => (string) Str::uuid(),
        ]);

        $this->assertSame(
            'completed',
            $transaction->transaction_status
        );

        $this->assertDatabaseHas('ledger_entries', [
            'transaction_id' => $transaction->transaction_id,
            'wallet_id' => $userWallet->wallet_id,
            'entry_type' => 'debit',
            'amount' => 40,
        ]);

        $this->assertDatabaseHas('ledger_entries', [
            'transaction_id' => $transaction->transaction_id,
            'wallet_id' => $clubWallet->wallet_id,
            'entry_type' => 'credit',
            'amount' => 40,
        ]);

        $balanceService = app(WalletBalanceService::class);

        $this->assertSame(
            60,
            $balanceService->getBalance($userWallet)
        );

        $this->assertSame(
            40,
            $balanceService->getBalance($clubWallet)
        );
    }

    public function test_user_cannot_transfer_ep_to_user(): void
    {
        [$sourceEntity, $sourceWallet] = $this->createEntityWithWallet(
            entityType: 'user',
            assetType: 'EP',
            externalSource: 'test',
            externalEntityId: 'user-1'
        );

        [, $destinationWallet] = $this->createEntityWithWallet(
            entityType: 'user',
            assetType: 'EP',
            externalSource: 'test',
            externalEntityId: 'user-2'
        );

        $this->issueToWallet(
            wallet: $sourceWallet,
            initiator: $sourceEntity,
            amount: 100
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'This economic transfer is not permitted.'
        );

        app(TransferProtocol::class)->execute([
            'source_wallet_id' => $sourceWallet->wallet_id,
            'destination_wallet_id' => $destinationWallet->wallet_id,
            'amount' => 40,
            'idempotency_key' => (string) Str::uuid(),
        ]);
    }

    public function test_club_cannot_transfer_ep_to_user(): void
    {
        [$clubEntity, $clubWallet] = $this->createEntityWithWallet(
            entityType: 'club',
            assetType: 'EP',
            externalSource: 'perfect10',
            externalEntityId: 'club-1'
        );

        [, $userWallet] = $this->createEntityWithWallet(
            entityType: 'user',
            assetType: 'EP',
            externalSource: 'test',
            externalEntityId: 'user-1'
        );

        $this->issueToWallet(
            wallet: $clubWallet,
            initiator: $clubEntity,
            amount: 100
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'This economic transfer is not permitted.'
        );

        app(TransferProtocol::class)->execute([
            'source_wallet_id' => $clubWallet->wallet_id,
            'destination_wallet_id' => $userWallet->wallet_id,
            'amount' => 40,
            'idempotency_key' => (string) Str::uuid(),
        ]);
    }

    public function test_transfer_fails_with_insufficient_balance(): void
    {
        [$userEntity, $userWallet] = $this->createEntityWithWallet(
            entityType: 'user',
            assetType: 'EP',
            externalSource: 'test',
            externalEntityId: 'user-1'
        );

        [, $clubWallet] = $this->createEntityWithWallet(
            entityType: 'club',
            assetType: 'EP',
            externalSource: 'perfect10',
            externalEntityId: 'club-1'
        );

        $this->issueToWallet(
            wallet: $userWallet,
            initiator: $userEntity,
            amount: 25
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Insufficient wallet balance.'
        );

        app(TransferProtocol::class)->execute([
            'source_wallet_id' => $userWallet->wallet_id,
            'destination_wallet_id' => $clubWallet->wallet_id,
            'amount' => 40,
            'idempotency_key' => (string) Str::uuid(),
        ]);
    }

    public function test_transfer_fails_when_source_wallet_is_suspended(): void
    {
        [$userEntity, $userWallet] = $this->createEntityWithWallet(
            entityType: 'user',
            assetType: 'EP',
            externalSource: 'test',
            externalEntityId: 'user-1'
        );

        [, $clubWallet] = $this->createEntityWithWallet(
            entityType: 'club',
            assetType: 'EP',
            externalSource: 'perfect10',
            externalEntityId: 'club-1'
        );

        $this->issueToWallet(
            wallet: $userWallet,
            initiator: $userEntity,
            amount: 100
        );

        $userWallet->update([
            'wallet_status' => 'suspended',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Source wallet is not active.'
        );

        app(TransferProtocol::class)->execute([
            'source_wallet_id' => $userWallet->wallet_id,
            'destination_wallet_id' => $clubWallet->wallet_id,
            'amount' => 40,
            'idempotency_key' => (string) Str::uuid(),
        ]);
    }

    public function test_transfer_fails_when_destination_wallet_is_suspended(): void
    {
        [$userEntity, $userWallet] = $this->createEntityWithWallet(
            entityType: 'user',
            assetType: 'EP',
            externalSource: 'test',
            externalEntityId: 'user-1'
        );

        [, $clubWallet] = $this->createEntityWithWallet(
            entityType: 'club',
            assetType: 'EP',
            externalSource: 'perfect10',
            externalEntityId: 'club-1'
        );

        $this->issueToWallet(
            wallet: $userWallet,
            initiator: $userEntity,
            amount: 100
        );

        $clubWallet->update([
            'wallet_status' => 'suspended',
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Destination wallet is not active.'
        );

        app(TransferProtocol::class)->execute([
            'source_wallet_id' => $userWallet->wallet_id,
            'destination_wallet_id' => $clubWallet->wallet_id,
            'amount' => 40,
            'idempotency_key' => (string) Str::uuid(),
        ]);
    }

    public function test_transfer_fails_between_different_asset_types(): void
    {
        [$userEntity, $userWallet] = $this->createEntityWithWallet(
            entityType: 'user',
            assetType: 'EP',
            externalSource: 'test',
            externalEntityId: 'user-1'
        );

        [, $businessWallet] = $this->createEntityWithWallet(
            entityType: 'business',
            assetType: 'BEP',
            externalSource: 'test',
            externalEntityId: 'business-1'
        );

        $this->issueToWallet(
            wallet: $userWallet,
            initiator: $userEntity,
            amount: 100
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Transfers must use wallets of the same asset type.'
        );

        app(TransferProtocol::class)->execute([
            'source_wallet_id' => $userWallet->wallet_id,
            'destination_wallet_id' => $businessWallet->wallet_id,
            'amount' => 40,
            'idempotency_key' => (string) Str::uuid(),
        ]);
    }

    public function test_same_idempotency_key_does_not_duplicate_transfer(): void
    {
        [$userEntity, $userWallet] = $this->createEntityWithWallet(
            entityType: 'user',
            assetType: 'EP',
            externalSource: 'test',
            externalEntityId: 'user-1'
        );

        [, $clubWallet] = $this->createEntityWithWallet(
            entityType: 'club',
            assetType: 'EP',
            externalSource: 'perfect10',
            externalEntityId: 'club-1'
        );

        $this->issueToWallet(
            wallet: $userWallet,
            initiator: $userEntity,
            amount: 100
        );

        $idempotencyKey = (string) Str::uuid();

        $protocol = app(TransferProtocol::class);

        $first = $protocol->execute([
            'source_wallet_id' => $userWallet->wallet_id,
            'destination_wallet_id' => $clubWallet->wallet_id,
            'amount' => 40,
            'idempotency_key' => $idempotencyKey,
        ]);

        $second = $protocol->execute([
            'source_wallet_id' => $userWallet->wallet_id,
            'destination_wallet_id' => $clubWallet->wallet_id,
            'amount' => 40,
            'idempotency_key' => $idempotencyKey,
        ]);

        $this->assertSame(
            $first->transaction_id,
            $second->transaction_id
        );

        $this->assertDatabaseCount(
            'ledger_transactions',
            2
        );

        $balanceService = app(WalletBalanceService::class);

        $this->assertSame(
            60,
            $balanceService->getBalance($userWallet)
        );

        $this->assertSame(
            40,
            $balanceService->getBalance($clubWallet)
        );
    }

    public function test_idempotency_key_cannot_be_reused_for_different_transfer(): void
    {
        [$userEntity, $userWallet] = $this->createEntityWithWallet(
            entityType: 'user',
            assetType: 'EP',
            externalSource: 'test',
            externalEntityId: 'user-1'
        );

        [, $clubWallet] = $this->createEntityWithWallet(
            entityType: 'club',
            assetType: 'EP',
            externalSource: 'perfect10',
            externalEntityId: 'club-1'
        );

        $this->issueToWallet(
            wallet: $userWallet,
            initiator: $userEntity,
            amount: 100
        );

        $idempotencyKey = (string) Str::uuid();

        $protocol = app(TransferProtocol::class);

        $protocol->execute([
            'source_wallet_id' => $userWallet->wallet_id,
            'destination_wallet_id' => $clubWallet->wallet_id,
            'amount' => 40,
            'idempotency_key' => $idempotencyKey,
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Idempotency key has already been used for a different transfer.'
        );

        $protocol->execute([
            'source_wallet_id' => $userWallet->wallet_id,
            'destination_wallet_id' => $clubWallet->wallet_id,
            'amount' => 50,
            'idempotency_key' => $idempotencyKey,
        ]);
    }

    private function createEntityWithWallet(
        string $entityType,
        string $assetType,
        string $externalSource,
        string $externalEntityId
    ): array {
        $entity = EconomicEntity::create([
            'entity_type' => $entityType,
            'external_source' => $externalSource,
            'external_entity_id' => $externalEntityId,
        ]);

        $wallet = Wallet::create([
            'economic_entity_id' => $entity->economic_entity_id,
            'asset_type' => $assetType,
            'wallet_status' => 'active',
        ]);

        return [
            $entity,
            $wallet,
        ];
    }

    private function issueToWallet(
        Wallet $wallet,
        EconomicEntity $initiator,
        int $amount
    ): void {
        $issuance = LedgerTransaction::create([
            'transaction_type' => 'issuance',
            'transaction_status' => 'pending',
            'initiated_by_entity_id' => $initiator->economic_entity_id,
        ]);

        LedgerEntry::create([
            'transaction_id' => $issuance->transaction_id,
            'wallet_id' => $wallet->wallet_id,
            'entry_sequence' => 1,
            'entry_type' => 'issuance',
            'amount' => $amount,
        ]);

        $issuance->update([
            'transaction_status' => 'completed',
            'completed_at' => now(),
        ]);
    }
}