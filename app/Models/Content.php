<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Content extends Model
{
    use HasFactory;

    protected $casts = [
        'json_content' => 'array'
    ];

    protected $fillable = [
        'type',
        'json_content',
        'options'
    ];
}
