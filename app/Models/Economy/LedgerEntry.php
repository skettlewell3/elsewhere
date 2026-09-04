<?php

namespace App\Models\Economy;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class LedgerEntry extends Model
{
    use HasUuids;

    protected $table = 'ledger_entries';

    protected $primaryKey = 'entry_id';

    public $incrementing = false;

    protected $keyType = 'string';

    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'amount' => 'integer',
        'entry_sequence' => 'integer',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(
            LedgerTransaction::class,
            'transaction_id',
            'transaction_id'
        );
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(
            Wallet::class,
            'wallet_id',
            'wallet_id'
        );
    }
}