<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Business extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'latitude',
        'longitude',
        'website_url',
        'is_active',
        'offers_ep_redemption',
        'location_id',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'offers_ep_redemption' => 'boolean',
        ];
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(
            BusinessCategory::class,
            'business_business_category'
        );
    }
}