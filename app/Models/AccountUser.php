<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountUser extends Authenticatable
{
    protected $table = 'accounts_users';

    protected $primaryKey = 'auth_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'auth_id',
        'account_id',
        'username',
        'first_name',
        'last_name',
        'is_founding_member',
    ];

    protected function casts(): array
    {
        return [
            'is_founding_member' => 'boolean',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(
            Account::class,
            'account_id',
            'account_id'
        );
    }
}
