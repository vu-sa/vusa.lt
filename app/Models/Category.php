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

    public function banners()
    {
        return $this->belongsTo(Banner::class, 'alias', 'category');
    }
}
