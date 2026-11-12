<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class App extends Model
{
    protected $table = 'apps';

    protected $primaryKey = 'app_id';

    protected $fillable = [
        'slug',
        'name',
        'category',
        'description',
        'image',
        'alt',
        'status',
        'info_url',
        'launch_url',
        'sort_order',
    ];
}
