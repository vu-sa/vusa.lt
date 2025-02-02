<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Scout\Searchable;

class QuickLink extends Model
{
    use HasFactory, Searchable;

    protected $guarded = [];

    public function toSearchableArray()
    {
        return [
            'text' => $this->text,
            'link' => $this->link,
        ];
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
