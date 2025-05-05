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
            'id' => (string) $this->id,
            'text' => $this->text,
            'link' => $this->link,
            'tenant_id' => $this->tenant_id,
            'lang' => $this->lang,
            'created_at' => $this->created_at->timestamp,
            'updated_at' => $this->updated_at->timestamp,
        ];
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
