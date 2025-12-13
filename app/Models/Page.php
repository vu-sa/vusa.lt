<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Laravel\Scout\Searchable;
use Spatie\Feed\Feedable;
use Spatie\Feed\FeedItem;
use Spatie\SchemaOrg\Organization;
use Spatie\SchemaOrg\WebPage;
use Spatie\Sitemap\Contracts\Sitemapable;
use Spatie\Sitemap\Tags\Url;

/**
 * @property int $id
 * @property string $title
 * @property string|null $permalink
 * @property string $lang
 * @property int|null $other_lang_id
 * @property int $content_id
 * @property int|null $category_id
 * @property bool $is_active
 * @property array $highlights
 * @property string $layout
 * @property string|null $featured_image
 * @property string|null $meta_description
 * @property Carbon|null $publish_time
 * @property int $tenant_id
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $last_edited_at
 * @property Carbon|null $deleted_at
 * @property-read \App\Models\Category|null $category
 * @property-read \App\Models\Content $content
 * @property-read Page|null $otherLanguagePage
 * @property-read \App\Models\Tenant $tenant
 *
 * @method static \Database\Factories\PageFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Page newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Page newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Page onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Page query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Page withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Page withoutTrashed()
 *
 * @mixin \Eloquent
 */
class Page extends Model implements Feedable, Sitemapable
{
    use HasFactory, Searchable, SoftDeletes;

    protected $guarded = [];

    /**
     * Available layout options for pages.
     */
    public const LAYOUTS = ['default', 'wide', 'focused'];

    protected $casts = [
        'updated_at' => 'datetime:Y-m-d H:i:s',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'last_edited_at' => 'datetime:Y-m-d H:i:s',
        'publish_time' => 'datetime',
        'is_active' => 'boolean',
        'highlights' => 'array',
    ];

    protected static function booted()
    {
        static::saving(function ($page) {
            // Ensure highlights is limited to 3 items
            if (is_array($page->highlights) && count($page->highlights) > 3) {
                $page->highlights = array_slice($page->highlights, 0, 3);
            }

            // Validate layout
            if (! in_array($page->layout, self::LAYOUTS)) {
                $page->layout = 'default';
            }
        });

        static::saved(function ($page) {
            Cache::tags(['sitemap', 'pages', "tenant_{$page->tenant_id}", "locale_{$page->lang}"])->flush();
        });

        static::deleted(function ($page) {
            Cache::tags(['sitemap', 'pages', "tenant_{$page->tenant_id}", "locale_{$page->lang}"])->flush();
        });
    }

    /**
     * Get the highlights, ensuring max 3 items.
     */
    public function getHighlightsAttribute($value): array
    {
        $highlights = is_string($value) ? json_decode($value, true) : $value;

        if (! is_array($highlights)) {
            return [];
        }

        return array_slice(array_filter($highlights), 0, 3);
    }

    /**
     * Mark the content as edited now.
     */
    public function markAsEdited(): void
    {
        $this->update(['last_edited_at' => now()]);
    }

    /**
     * Get the featured image URL.
     */
    public function getFeaturedImageUrl(): ?string
    {
        if (! $this->featured_image) {
            return null;
        }

        if (str_starts_with($this->featured_image, 'http')) {
            return $this->featured_image;
        }

        return Storage::url($this->featured_image);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function otherLanguagePage(): BelongsTo
    {
        return $this->belongsTo(Page::class, 'other_lang_id');
    }

    // Keep backwards compatibility
    public function getOtherLanguage()
    {
        return $this->otherLanguagePage;
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function content()
    {
        return $this->belongsTo(Content::class);
    }

    /**
     * Generate JSON-LD WebPage schema for SEO.
     */
    public function toWebPageSchema(): WebPage
    {
        $schema = new WebPage;

        $schema = $schema->name($this->title);
        $schema = $schema->url(url('/'.$this->permalink));
        $schema = $schema->inLanguage($this->lang);
        $schema = $schema->datePublished($this->publish_time ?? $this->created_at);
        $schema = $schema->dateModified($this->last_edited_at ?? $this->updated_at);

        // Add description
        if ($this->meta_description) {
            $schema = $schema->description($this->meta_description);
        }

        // Add featured image
        if ($imageUrl = $this->getFeaturedImageUrl()) {
            $schema = $schema->image($imageUrl);
        }

        // Add highlights as main content summary
        if (! empty($this->highlights)) {
            $schema = $schema->abstract(implode(' • ', $this->highlights));
        }

        // Add publisher organization
        $organization = (new Organization)
            ->name($this->tenant->shortname)
            ->url(url('/'));

        if ($this->tenant->fullname) {
            $organization = $organization->alternateName($this->tenant->fullname);
        }

        $schema = $schema->publisher($organization);
        $schema = $schema->mainEntityOfPage(url('/'.$this->permalink));

        return $schema;
    }

    /**
     * Convert page to RSS feed item.
     */
    public function toFeedItem(): FeedItem
    {
        $summary = $this->meta_description ?? $this->title;

        // Add featured image if available
        if ($imageUrl = $this->getFeaturedImageUrl()) {
            $summary = '<img src="'.$imageUrl.'" alt="'.$this->title.'" style="max-width: 100%; height: auto; margin-bottom: 1rem">'.$summary;
        }

        return FeedItem::create()
            ->id($this->id)
            ->title($this->title)
            ->summary($summary)
            ->updated($this->last_edited_at ?? $this->updated_at)
            ->link($this->permalink)
            ->authorName($this->tenant->shortname);
    }

    /**
     * Get feed items for RSS.
     */
    public static function getFeedItems()
    {
        return Page::query()
            ->where('is_active', true)
            ->whereNotNull('publish_time')
            ->where('publish_time', '<=', now())
            ->orderByDesc('publish_time')
            ->take(15)
            ->get();
    }

    public function toSearchableArray()
    {
        return [
            'id' => (string) $this->id,
            'title' => $this->title,
            'permalink' => $this->permalink,
            'meta_description' => $this->meta_description,
            'lang' => $this->lang,
            'tenant_name' => $this->tenant->fullname,
            'category_name' => $this->category?->name,
            'created_at' => $this->created_at->timestamp,
        ];
    }

    /**
     * Determine if the model should be searchable.
     */
    public function shouldBeSearchable()
    {
        // Only index active pages that are published
        if (! $this->is_active) {
            return false;
        }

        // If publish_time is set, check if it's in the past
        if ($this->publish_time) {
            return $this->publish_time->isPast();
        }

        return true;
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
            ->setLastModificationDate($this->last_edited_at ?? $this->updated_at)
            ->setPriority(0.7)
            ->setChangeFrequency(Url::CHANGE_FREQUENCY_MONTHLY);

        // Add featured image if available
        if ($imageUrl = $this->getFeaturedImageUrl()) {
            $sitemapUrl->addImage($imageUrl, $this->title);
        }

        // Add alternate language links if available
        if ($this->other_lang_id) {
            $otherLangPage = $this->otherLanguagePage;
            if ($otherLangPage) {
                $sitemapUrl->addAlternate(url('/'.$otherLangPage->permalink), $otherLangPage->lang);
            }
        }

        return $sitemapUrl;
    }
}
