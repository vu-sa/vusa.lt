<?php

namespace App\Models;

use App\Models\Traits\HasTranslations;
use Database\Factories\SupportRequestAreaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['support_service_id', 'name', 'slug', 'is_active', 'sort_order'])]
class SupportRequestArea extends Model
{
    /** @use HasFactory<SupportRequestAreaFactory> */
    use HasFactory, HasTranslations;

    public array $translatable = ['name'];

    public function service(): BelongsTo
    {
        return $this->belongsTo(SupportService::class, 'support_service_id');
    }

    public function requests(): HasMany
    {
        return $this->hasMany(SupportRequest::class);
    }
}
