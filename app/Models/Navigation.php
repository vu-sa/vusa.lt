<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Navigation extends Model
{
    use HasFactory;
    
    protected $table = 'navigation';

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function padalinys()
    {
        return $this->belongsTo(Padalinys::class, 'padalinys_id');
    }

}
