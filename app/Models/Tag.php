<?php

namespace App\Models;

use App\Models\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    use HasFactory, HasTranslations;

    public $translatable = ['name', 'description'];

    protected $fillable = [
        'name',
        'description', 
        'alias',
    ];

    public function news(): BelongsToMany
    {
        return $this->belongsToMany(News::class, 'posts_tags', 'tag_id', 'news_id');
    }

    // Future: add pages relationship when implemented
    // public function pages(): BelongsToMany
    // {
    //     return $this->belongsToMany(Page::class, 'posts_tags', 'tag_id', 'page_id');
    // }
}
