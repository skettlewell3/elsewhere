<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Services\CanonicalLocationResolver;

class BusinessLocation extends Model
{
    protected $fillable = [
        'business_id',
        'location_id',
        'canonical_location_id',
        'name',
        'address_line_1',
        'address_line_2',
        'postcode',
        'latitude',
        'longitude',
        'phone',
        'email',
        'is_primary',
        'is_mobile',
        'is_active',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'is_primary' => 'boolean',
        'is_mobile' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
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

    protected static function booted(): void
    {
        static::saving(function (BusinessLocation $businessLocation) {
            if (!$businessLocation->location_id) {
                return;
            }
        
            if (
                !$businessLocation->exists ||
                $businessLocation->isDirty('location_id') ||
                !$businessLocation->canonical_location_id
            ) {
                $resolver = app(CanonicalLocationResolver::class);
            
                $canonicalLocation = $resolver->resolve(
                    (int) $businessLocation->location_id
                );
            
                $businessLocation->canonical_location_id = $canonicalLocation->id;
            }
        });
    }
}