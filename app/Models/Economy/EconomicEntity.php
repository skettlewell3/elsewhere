<?php

namespace App\Models\Economy;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EconomicEntity extends Model
{
    protected $table = 'economic_entities';

    protected $primaryKey = 'economic_entity_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    public function wallets(): HasMany
    {
        return $this->hasMany(
            Wallet::class,
            'economic_entity_id',
            'economic_entity_id'
        );
    }

    public function initiatedTransactions(): HasMany
    {
        return $this->hasMany(
            LedgerTransaction::class,
            'initiated_by_entity_id',
            'economic_entity_id'
        );
    }
}