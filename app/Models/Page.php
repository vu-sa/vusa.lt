<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Laravel\Scout\Searchable;
use Spatie\Sitemap\Contracts\Sitemapable;
use Spatie\Sitemap\Tags\Url;

class Page extends Model implements Sitemapable
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
            // Clear sitemap cache when page is updated
            Cache::tags(['sitemap', 'pages', "tenant_{$page->tenant_id}"])->flush();
        });

        static::deleted(function ($page) {
            // Clear sitemap cache when page is deleted
            Cache::tags(['sitemap', 'pages', "tenant_{$page->tenant_id}"])->flush();
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

    public function toSitemapTag(): Url
    {
        $sitemapUrl = Url::create('/'.$this->permalink)
            ->setLastModificationDate($this->updated_at)
            ->setPriority(0.7)
            ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY);

        // Add alternate language links if available
        if ($this->other_lang_id) {
            $otherLangPage = $this->getOtherLanguage();
            if ($otherLangPage) {
                $sitemapUrl->addAlternate(url('/'.$otherLangPage->permalink), $otherLangPage->lang);
            }
        }

        return $sitemapUrl;
    }
}
