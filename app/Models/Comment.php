<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $with = ['user:id,name,profile_photo_path'];

    public function commentable()
    {
        return $this->morphTo();
    }

    // create a nested comment
    public function comment(string $comment)
    {
        $this->comments()->create();
    }

    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
