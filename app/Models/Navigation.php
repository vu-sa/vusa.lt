<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Navigation extends Model
{
    use HasFactory;

    protected $table = 'navigation';

    protected $hidden = ['created_at', 'updated_at'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function padalinys()
    {
        return $this->belongsTo(Padalinys::class, 'padalinys_id');
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
