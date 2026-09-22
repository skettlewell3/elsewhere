<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Services\CanonicalLocationResolver;

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

    public function canonicalLocation(): BelongsTo
    {
        return $this->belongsTo(
            Location::class,
            'canonical_location_id'
        );
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(
            BusinessCategory::class,
            'business_business_category'
        );
    }

    protected static function booted(): void
    {
        static::saving(function (Business $business) {
            if (!$business->location_id) {
                return;
            }
        
            if (
                !$business->exists ||
                $business->isDirty('location_id') ||
                !$business->canonical_location_id
            ) {
                $resolver = app(CanonicalLocationResolver::class);
            
                $canonicalLocation = $resolver->resolve(
                    (int) $business->location_id
                );
            
                $business->canonical_location_id = $canonicalLocation->id;
            }
        });
    }
}