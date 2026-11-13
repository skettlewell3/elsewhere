<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppMembership extends Model
{
    protected $table = 'app_memberships';

    protected $primaryKey = 'app_membership_id';

    protected $fillable = [
        'auth_id',
        'app_id',
        'status',
        'registered_at',
    ];

    protected function casts(): array
    {
        return [
            'registered_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(
            AccountUser::class,
            'auth_id',
            'auth_id'
        );
    }

    public function app(): BelongsTo
    {
        return $this->belongsTo(
            App::class,
            'app_id',
            'app_id'
        );
    }
}
