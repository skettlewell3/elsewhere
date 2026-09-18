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
        'latitude',
        'longitude',
        'map_zoom',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'map_zoom' => 'float',
        ];
    }

    public function locations(): HasMany
    {
        return $this->hasMany(Location::class);
    }
}