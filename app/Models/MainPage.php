<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MainPage extends Model
{
    use HasFactory;

    protected $table = 'main_page';

    protected $guarded = [];

    public function padalinys()
    {
        return $this->belongsTo(Padalinys::class, 'padalinys_id');
    }
}
