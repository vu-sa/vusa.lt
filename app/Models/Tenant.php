<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Laravel\Scout\Searchable;
use Staudenmeir\EloquentHasManyDeep\HasManyDeep;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

class Tenant extends Model
{
    use HasFactory, HasRelationships, Searchable;

    protected $guarded = [];

    public $timestamps = false;

    public function banners(): HasMany
    {
        return $this->hasMany(Banner::class);
    }

    public function calendar(): HasMany
    {
        return $this->hasMany(Calendar::class);
    }

    public function duties(): HasManyThrough
    {
        return $this->hasManyThrough(Duty::class, Institution::class, 'tenant_id', 'institution_id');
    }

    public function institutions(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Institution::class);
    }

    public function news(): HasMany
    {
        return $this->hasMany(News::class);
    }

    public function pages(): HasMany
    {
        return $this->hasMany(Page::class);
    }

    public function quickLinks(): HasMany
    {
        return $this->hasMany(QuickLink::class);
    }

    public function resources(): HasMany
    {
        return $this->hasMany(Resource::class);
    }

    public function users(): HasManyDeep
    {
        return $this->hasManyDeepFromRelations($this->institutions(), (new Institution)->duties(), (new Duty)->current_users());
    }

    public function reservations(): HasManyDeep
    {
        return $this->hasManyDeepFromRelations($this->users(), (new User)->reservations());
    }

    public function tenant()
    {
        return $this;
    }

    public function primary_institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class, 'primary_institution_id');
    }

    public function content()
    {
        return $this->belongsTo(Content::class);
    }
}
