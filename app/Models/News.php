<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Laravel\Scout\Searchable;
use Spatie\Feed\Feedable;
use Spatie\Feed\FeedItem;

class News extends Model implements Feedable
{
    use HasFactory, Searchable, SoftDeletes;

    protected $table = 'news';

    protected $guarded = [];

    protected $casts = [
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

    public function tags()
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

    public static function getFeedItems()
    {
        return News::orderByDesc('publish_time')->take(15)->get();
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
