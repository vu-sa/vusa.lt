<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Feed\Feedable;
use Spatie\Feed\FeedItem;
use Illuminate\Support\Carbon;

class News extends Model implements Feedable
{
    use HasFactory;

    protected $table = 'news';

    protected $guarded = [];

    protected $casts = [
        'updated_at' => 'datetime:Y-m-d H:i:s',
        'created_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function padalinys()
    {
        return $this->belongsTo(Padalinys::class, 'padalinys_id');
    }

    // Get another language news
    public function getOtherLanguage()
    {
        return News::find($this->other_lang_id);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'posts_tags', 'news_id', 'tag_id');
    }

    public function toFeedItem(): FeedItem
    {
        return FeedItem::create()
            ->id($this->id)
            ->title($this->title)
            ->summary($this->short)
            ->updated(Carbon::parse($this->publish_time))
            ->image($this->image) // TODO: fix, as this doesn't show an image
            ->link('naujiena/' . $this->permalink)
            ->authorName($this->padalinys->shortname);
    }

    public static function getFeedItems()
    {
        return News::orderByDesc('publish_time')->take(15)->get();
    }
}
