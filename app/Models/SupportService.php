<?php

namespace App\Models;

use App\Models\Traits\HasTranslations;
use Database\Factories\SupportServiceFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'is_active', 'sort_order'])]
class SupportService extends Model
{
    /** @use HasFactory<SupportServiceFactory> */
    use HasFactory, HasTranslations;

    public array $translatable = ['name'];

    public function areas(): HasMany
    {
        return $this->hasMany(SupportRequestArea::class);
    }
}
