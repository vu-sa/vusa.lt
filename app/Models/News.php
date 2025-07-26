<?php

namespace App\Models;

use App\Casts\NewsImage;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Laravel\Scout\Searchable;
use Spatie\Feed\Feedable;
use Spatie\Feed\FeedItem;
use Spatie\SchemaOrg\NewsArticle;
use Spatie\SchemaOrg\Organization;
use Spatie\Sitemap\Contracts\Sitemapable;
use Spatie\Sitemap\Tags\Url;

class News extends Model implements Feedable, Sitemapable
{
    use HasFactory, Searchable, SoftDeletes;

    protected $table = 'news';

    protected $guarded = [];

    public $fallback_image = '/images/icons/naujienu_foto.png';

    protected $casts = [
        'image' => NewsImage::class,
        'updated_at' => 'datetime:Y-m-d H:i:s',
        'created_at' => 'datetime:Y-m-d H:i:s',
        // TODO: convert to datetime in database
        'publish_time' => 'datetime',
    ];

    protected static function booted()
    {
        static::saved(function ($news) {
            // Clear sitemap cache when news is updated
            Cache::tags(['sitemap', 'news', "tenant_{$news->tenant_id}"])->flush();
        });

        static::deleted(function ($news) {
            // Clear sitemap cache when news is deleted
            Cache::tags(['sitemap', 'news', "tenant_{$news->tenant_id}"])->flush();
        });
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tenant(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function other_language_news(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(News::class, 'id', 'other_lang_id');
    }

    public function tags(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'posts_tags', 'news_id', 'tag_id');
    }

    public function content(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Content::class);
    }

    public function toFeedItem(): FeedItem
    {
        // add image to short
        $short = '<img src="'.config('app.url').'/uploads\/'.$this->image.'" alt="'.$this->title.'" style="max-width: 100%; height: auto; object-position: cover; margin-bottom: 2rem">'.$this->short;

        return FeedItem::create()
            ->id($this->id)
            ->title($this->title)
            ->summary($short)
            ->updated(Carbon::parse($this->publish_time))
            // image with hostname
            ->link('naujiena/'.$this->permalink)
            ->authorName($this->tenant->shortname);
    }

    public function getImageUrl()
    {
        if (substr($this->image, 0, 4) === 'http') {
            return $this->image;
        } else {
            return Storage::get(str_replace('uploads', 'public', $this->image)) === null ? '/images/icons/naujienu_foto.png' : $this->image;
        }
    }

    public function toNewsArticleSchema()
    {
        $schema = new NewsArticle;

        // Fix image URL construction
        $imageUrl = substr($this->image, 0, 4) === 'http' ? $this->image : url($this->getImageUrl());
        $schema = $schema->image($imageUrl);

        $schema = $schema->datePublished($this->publish_time);
        $schema = $schema->dateModified($this->updated_at);
        $schema = $schema->headline($this->title);

        // Add description from short field
        if ($this->short) {
            $schema = $schema->description(strip_tags($this->short));
        }

        // Create proper organization with website URL
        $organization = (new Organization)
            ->name($this->tenant->shortname)
            ->url(url('/'));

        // Add full organization name if available
        if ($this->tenant->fullname) {
            $organization = $organization->alternateName($this->tenant->fullname);
        }

        $schema = $schema->author($organization);
        $schema = $schema->publisher($organization);

        // Add main entity of page (canonical URL)
        $schema = $schema->mainEntityOfPage(url('/naujiena/'.$this->permalink));

        // Add article URL
        $schema = $schema->url(url('/naujiena/'.$this->permalink));

        return $schema;
    }

    public static function getFeedItems()
    {
        return News::query()->where('draft', false)->orderByDesc('publish_time')->take(15)->get();
    }

    public function toSearchableArray()
    {
        return [
            'id' => (string) $this->id,
            'title' => $this->title,
            'short' => $this->short,
            'permalink' => $this->permalink,
            'image' => $this->image,
            'publish_time' => $this->publish_time ? $this->publish_time->timestamp : now()->timestamp,
            'lang' => $this->lang,
            'tenant_name' => $this->tenant ? $this->tenant->fullname : null,
            'created_at' => $this->created_at->timestamp,
        ];
    }

    /**
     * Determine if the model should be searchable.
     */
    public function shouldBeSearchable()
    {
        // Only index published (non-draft) news that has been published
        return ! $this->draft &&
               $this->publish_time &&
               $this->publish_time->isPast();
    }

    /**
     * Get the engine used to index the model.
     * News should use Typesense for public search.
     */
    public function searchableUsing()
    {
        return app(\Laravel\Scout\EngineManager::class)->engine('typesense');
    }

    public function toSitemapTag(): Url
    {
        $url = $this->lang === 'lt' ? '/naujiena/' : '/news/';
        $url .= $this->permalink;

        $sitemapUrl = Url::create($url)
            ->setLastModificationDate($this->updated_at)
            ->setPriority(0.6)
            ->setChangeFrequency(Url::CHANGE_FREQUENCY_NEVER);

        // Add image if available
        if ($this->image) {
            $imageUrl = substr($this->image, 0, 4) === 'http' ? $this->image : url($this->getImageUrl());
            $sitemapUrl->addImage($imageUrl, $this->title);
        }

        // Add alternate language links if available
        if ($this->other_language_news) {
            $otherLangUrl = $this->other_language_news->lang === 'lt' ? '/naujiena/' : '/news/';
            $otherLangUrl .= $this->other_language_news->permalink;

            $sitemapUrl->addAlternate(url($otherLangUrl), $this->other_language_news->lang);
        }

        return $sitemapUrl;
    }
}
