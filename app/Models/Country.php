<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Country extends Model
{
    protected $fillable = [
        'name',
        'code',
        'slug',
        'default_locale',
    ];

    public function locations(): HasMany
    {
        return $this->hasMany(Location::class);
    }
}