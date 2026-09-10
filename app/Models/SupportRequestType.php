<?php

namespace App\Models;

use App\Models\Traits\HasTranslations;
use Database\Factories\SupportRequestTypeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'is_active', 'sort_order'])]
class SupportRequestType extends Model
{
    /** @use HasFactory<SupportRequestTypeFactory> */
    use HasFactory, HasTranslations;

    public array $translatable = ['name'];

    public function requests(): HasMany
    {
        return $this->hasMany(SupportRequest::class);
    }
}
