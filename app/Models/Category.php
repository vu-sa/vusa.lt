<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $table = 'categories';

    protected $fillable = [
        'name',
        'alias',
        'description',
    ];

    public function pages()
    {
        return $this->hasMany(Page::class);
    }

    public function news()
    {
        return $this->hasMany(News::class);
    }

    public function calendars()
    {
        return $this->hasMany(Calendar::class);
    }
}
