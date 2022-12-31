<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'created_at' => 'timestamp'
    ];

    public function taskable()
    {
        return $this->morphTo();
    }

    public function users()
    {
        return $this->belongsToMany(User::class);
    }
}
