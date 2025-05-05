<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;

class Page extends Model
{
    use HasFactory, Searchable, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'updated_at' => 'datetime:Y-m-d H:i:s',
        'created_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    // Get another language page
    public function getOtherLanguage()
    {
        return Page::find($this->other_lang_id);
    }

    // Category
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function content()
    {
        return $this->belongsTo(Content::class);
    }

    public function toSearchableArray()
    {
        return [
            'id' => (string) $this->id,
            'title' => $this->title,
            'permalink' => $this->permalink,
            // 'content' => $this->content,
            'lang' => $this->lang,
            'tenant_id' => $this->tenant_id,
            'tenant_name' => $this->tenant ? $this->tenant->name : null,
            'category_id' => $this->category_id,
            'category_name' => $this->category ? $this->category->name : null,
            'created_at' => $this->created_at->timestamp,
            'updated_at' => $this->updated_at->timestamp,
        ];
    }
}
