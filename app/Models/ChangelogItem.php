<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChangelogItem extends Model
{
    use HasFactory, Traits\HasTranslations;

    public $translatable = ['title', 'description'];

    public $timestamps = false;

    protected $casts = [
        'date' => 'datetime',
    ];

    protected $fillable = [
        'title',
        'description',
        'date',
    ];
}
