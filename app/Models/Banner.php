<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Scout\Searchable;

class Banner extends Model
{
    use HasFactory, Searchable;

    protected $guarded = [];

    public function toSearchableArray()
    {
        return [
            'title' => $this->title,
        ];
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function tenants()
    {
        return $this->tenant();
    }
}
