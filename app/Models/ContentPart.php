<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContentPart extends Model
{
    use HasFactory;

    protected $casts = [
        'json_content' => 'array',
    ];

    protected $fillable = [
        'type',
        'json_content',
        'options',
    ];

    public function content() {
        return $this->belongsTo(Content::class);
    }
}
