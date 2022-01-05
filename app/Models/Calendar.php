<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Calendar extends Model
{
    use HasFactory;
    
    protected $table = 'calendar';

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function padalinys()
    {
        return $this->belongsTo(Padalinys::class, 'padalinys_id', 'id');
    }

    public function category()
    {
        return $this->hasOne(Category::class, 'category', 'alias');
    }
}
