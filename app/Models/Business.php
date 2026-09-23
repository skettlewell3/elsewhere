<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Business extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'website_url',
        'is_active',
        'offers_ep_redemption',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'offers_ep_redemption' => 'boolean',
        ];
    }

    public function locations(): HasMany
    {
        return $this->hasMany(BusinessLocation::class);
    }

    public function primaryLocation(): HasOne
    {
        return $this->hasOne(BusinessLocation::class)
            ->where('is_primary', true);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(
            BusinessCategory::class,
            'business_business_category'
        );
    }

    public function page(): HasOne
    {
        return $this->hasOne(BusinessPage::class);
    }
}