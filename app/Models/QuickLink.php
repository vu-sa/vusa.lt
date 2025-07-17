<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Cache;
use Laravel\Scout\Searchable;

class QuickLink extends Model
{
    use HasFactory, Searchable;

    protected $guarded = [];

    protected static function booted()
    {
        static::saved(function ($quickLink) {
            Cache::tags(['quick_links', "tenant_{$quickLink->tenant_id}", "locale_{$quickLink->lang}"])->flush();
        });

        static::deleted(function ($quickLink) {
            Cache::tags(['quick_links', "tenant_{$quickLink->tenant_id}", "locale_{$quickLink->lang}"])->flush();
        });
    }

    public function toSearchableArray()
    {
        return [
            'text' => $this->text,
            'link' => $this->link,
        ];
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
