<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Laravel\Scout\Searchable;

class Page extends Model
{
    use HasFactory, Searchable, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'updated_at' => 'datetime:Y-m-d H:i:s',
        'created_at' => 'datetime:Y-m-d H:i:s',
    ];

    protected static function booted()
    {
        static::saved(function ($page) {
            Cache::tags(['pages', "tenant_{$page->tenant_id}", "locale_{$page->lang}"])->flush();
        });

        static::deleted(function ($page) {
            Cache::tags(['pages', "tenant_{$page->tenant_id}", "locale_{$page->lang}"])->flush();
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    // Get another language page
    public function getOtherLanguage()
    {
        return Page::find($this->other_lang_id);
    }

    // Category
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function content()
    {
        return $this->belongsTo(Content::class);
    }

    public function toSearchableArray()
    {
        $array = $this->toArray();

        // Customize array...
        // return only title
        $array = [
            'title' => $this->title,
            'permalink' => $this->permalink,
        ];

        return $array;
    }
}
