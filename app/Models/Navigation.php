<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Navigation extends Model
{
    use HasFactory;

    protected $table = 'navigation';

    protected $guarded = [];

    protected $hidden = ['created_at', 'updated_at'];

    protected $casts = [
        'extra_attributes' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Get parent navigation
    public function parent()
    {
        if ($this->parent_id == 0) {
            return null;
        } else {
            return $this->belongsTo(Navigation::class, 'parent_id');
        }
    }
}
