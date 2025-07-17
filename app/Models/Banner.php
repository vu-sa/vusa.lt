<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Cache;
use Laravel\Scout\Searchable;

class Banner extends Model
{
    use HasFactory, Searchable;

    protected $guarded = [];

    protected static function booted()
    {
        static::saved(function ($banner) {
            Cache::tags(['banners', "tenant_{$banner->tenant_id}"])->flush();
        });

        static::deleted(function ($banner) {
            Cache::tags(['banners', "tenant_{$banner->tenant_id}"])->flush();
        });
    }

    public function toSearchableArray()
    {
        return [
            'title' => $this->title,
        ];
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function tenants()
    {
        return $this->tenant();
    }
}
