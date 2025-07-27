<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;
use Laravel\Scout\Searchable;
use Spatie\Sitemap\Contracts\Sitemapable;
use Spatie\Sitemap\Tags\Url;

/**
 * @property-read \App\Models\Category|null $category
 * @property-read \App\Models\Tenant|null $tenant
 */
class Page extends Model implements Sitemapable
{
    use HasFactory, Searchable, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'updated_at' => 'datetime:Y-m-d H:i:s',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'is_active' => 'boolean',
    ];

    protected static function booted()
    {
        static::saved(function ($page) {
            Cache::tags(['sitemap', 'pages', "tenant_{$page->tenant_id}", "locale_{$page->lang}"])->flush();
        });

        static::deleted(function ($page) {
            Cache::tags(['sitemap', 'pages', "tenant_{$page->tenant_id}", "locale_{$page->lang}"])->flush();
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
        return [
            'id' => (string) $this->id,
            'title' => $this->title,
            'permalink' => $this->permalink,
            'lang' => $this->lang,
            'tenant_name' => $this->tenant ? $this->tenant->fullname : null,
            'category_name' => $this->category?->name,
            'created_at' => $this->created_at->timestamp,
        ];
    }

    /**
     * Determine if the model should be searchable.
     */
    public function shouldBeSearchable()
    {
        // Only index active pages (published pages)
        return $this->is_active ?? false;
    }

    /**
     * Get the engine used to index the model.
     * Pages should use Typesense for public search.
     */
    public function searchableUsing()
    {
        return app(\Laravel\Scout\EngineManager::class)->engine('typesense');
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
