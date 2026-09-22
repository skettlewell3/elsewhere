<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessPage extends Model
{
    protected $fillable = [
        'business_id',
        'headline',
        'short_description',
        'about',
        'is_published',
    ];

    protected $casts = [
        'is_published' => 'boolean',
    ];

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}