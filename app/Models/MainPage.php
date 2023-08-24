<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Scout\Searchable;

class MainPage extends Model
{
    use HasFactory, Searchable;

    protected $table = 'main_page';

    protected $guarded = [];

    public function toSearchableArray()
    {
        return [
            'text' => $this->text,
            'link' => $this->link,
        ];
    }

    public function padalinys()
    {
        return $this->belongsTo(Padalinys::class, 'padalinys_id');
    }
}
