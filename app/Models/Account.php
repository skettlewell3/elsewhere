<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Account extends Model
{
    protected $table = 'accounts';

    protected $primaryKey = 'account_id';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'account_type',
        'username',
    ];

    public function userLink(): HasOne
    {
        return $this->hasOne(AccountUser::class, 'account_id', 'account_id');
    }
}
