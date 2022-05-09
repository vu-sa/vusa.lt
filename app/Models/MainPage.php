<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MainPage extends Model
{
    use HasFactory;
    
    protected $table = 'main_page';

    public function padalinys()
    {
        return $this->belongsTo(Padalinys::class, 'padalinys_id');
    }

    public function users()
    {
        return $this->hasMany(User::class, 'user_id');
    }
}
