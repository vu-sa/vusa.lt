<?php

namespace App\Models;

use App\Casts\NewsImage;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Laravel\Scout\Searchable;
use Spatie\Feed\Feedable;
use Spatie\Feed\FeedItem;
use Spatie\SchemaOrg\NewsArticle;
use Spatie\SchemaOrg\Organization;

class News extends Model implements Feedable
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

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tenant(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function other_language_news()
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

        $schema = $schema->image(! substr($this->image, 0, 4) === 'http' ? url($this->getImageUrl()) : $this->getImageUrl());

        $schema = $schema->datePublished($this->publish_time);

        $schema = $schema->dateModified($this->updated_at);

        $schema = $schema->headline($this->title);

        $schema->author((new Organization)->name($this->tenant->shortname));

        return $schema;
    }

    public static function getFeedItems()
    {
        return News::query()->where('draft', false)->orderByDesc('publish_time')->take(15)->get();
    }

    public function toSearchableArray()
    {
        $array = $this->toArray();

        // Customize array...
        // return only title
        $array = [
            'title' => $this->title,
        ];

        return $array;
    }
}
