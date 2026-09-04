<?php

namespace App\Models\Economy;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Wallet extends Model
{
    use HasUuids;

    protected $table = 'wallets';

    protected $primaryKey = 'wallet_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    public function economicEntity(): BelongsTo
    {
        return $this->belongsTo(
            EconomicEntity::class,
            'economic_entity_id',
            'economic_entity_id'
        );
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(
            LedgerEntry::class,
            'wallet_id',
            'wallet_id'
        );
    }
}