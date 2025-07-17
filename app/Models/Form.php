<?php

namespace App\Models;

use App\Models\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;

class Form extends Model
{
    /** @use HasFactory<\Database\Factories\FormFactory> */
    use HasFactory, HasTranslations, HasUlids, Searchable, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'path',
        'publish_time',
    ];

    public $translatable = [
        'name',
        'description',
        'path',
    ];

    public function toSearchableArray()
    {
        return [
            'name->'.app()->getLocale() => $this->getTranslation('name', app()->getLocale()),
            'path->'.app()->getLocale() => $this->getTranslation('path', app()->getLocale()),
        ];
    }

    public function formFields(): HasMany
    {
        return $this->hasMany(FormField::class);
    }

    public function registrations()
    {
        return $this->hasMany(Registration::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function training()
    {
        return $this->belongsTo(Training::class);
    }
}
