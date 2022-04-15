<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class News extends Model
{
    use HasFactory;
    
    protected $table = 'news';

    protected $casts = [
        'updated_at' => 'datetime:Y-m-d H:i:s',
        'created_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function user() {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function padalinys() {
        return $this->belongsTo(Padalinys::class, 'padalinys_id');
    }

    // Get another language news
    public function getOtherLanguage() {
        return News::find($this->other_lang_id);
    }

    public function tags() {
        return $this->belongsToMany(Tag::class, 'posts_tags', 'news_id', 'tag_id');
    }
}
