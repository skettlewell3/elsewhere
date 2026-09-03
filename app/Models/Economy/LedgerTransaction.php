<?php

namespace App\Models\Economy;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LedgerTransaction extends Model
{
    protected $table = 'ledger_transactions';

    protected $primaryKey = 'transaction_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected $casts = [
        'metadata' => 'array',
        'completed_at' => 'datetime',
    ];

    public function initiator(): BelongsTo
    {
        return $this->belongsTo(
            EconomicEntity::class,
            'initiated_by_entity_id',
            'economic_entity_id'
        );
    }

    public function entries(): HasMany
    {
        return $this->hasMany(
            LedgerEntry::class,
            'transaction_id',
            'transaction_id'
        )->orderBy('entry_sequence');
    }

    public function reversedTransaction(): BelongsTo
    {
        return $this->belongsTo(
            self::class,
            'reversal_of_transaction_id',
            'transaction_id'
        );
    }
}