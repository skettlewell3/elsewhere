<?php

namespace App\Services\Economy\Permissions;

use App\Models\Economy\Wallet;
use RuntimeException;

class TransferPermissionService
{
    public function assertAllowed(
        Wallet $sourceWallet,
        Wallet $destinationWallet
    ): void {
        $sourceWallet->loadMissing('economicEntity');
        $destinationWallet->loadMissing('economicEntity');

        $sourceEntity = $sourceWallet->economicEntity;
        $destinationEntity = $destinationWallet->economicEntity;

        if (!$sourceEntity || !$destinationEntity) {
            throw new RuntimeException(
                'Both wallets must belong to valid economic entities.'
            );
        }

        if ($sourceWallet->wallet_id === $destinationWallet->wallet_id) {
            throw new RuntimeException(
                'A wallet cannot transfer to itself.'
            );
        }

        if ($sourceWallet->asset_type !== $destinationWallet->asset_type) {
            throw new RuntimeException(
                'Transfers must use wallets of the same asset type.'
            );
        }

        if ($sourceWallet->wallet_status !== 'active') {
            throw new RuntimeException(
                'Source wallet is not active.'
            );
        }

        if ($destinationWallet->wallet_status !== 'active') {
            throw new RuntimeException(
                'Destination wallet is not active.'
            );
        }

        if (
            $sourceEntity->entity_type === 'user' &&
            $destinationEntity->entity_type === 'club' &&
            $sourceWallet->asset_type === 'EP'
        ) {
            return;
        }

        throw new RuntimeException(
            'This economic transfer is not permitted.'
        );
    }
}