<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class BusinessCategory extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'colour_key',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function businesses(): BelongsToMany
    {
        return $this->belongsToMany(
            Business::class,
            'business_business_category'
        );
    }
}