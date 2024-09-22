<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Scout\Searchable;
use Staudenmeir\EloquentHasManyDeep\HasRelationships;

class Tenant extends Model
{
    use HasFactory, Searchable, HasRelationships;

    protected $guarded = [];

    public $timestamps = false;

    public function banners()
    {
        return $this->hasMany(Banner::class);
    }

    public function calendar()
    {
        return $this->hasMany(Calendar::class);
    }

    public function duties()
    {
        return $this->hasManyThrough(Duty::class, Institution::class, 'tenant_id', 'institution_id');
    }

    public function institutions()
    {
        return $this->hasMany(Institution::class);
    }

    public function news()
    {
        return $this->hasMany(News::class);
    }

    public function pages()
    {
        return $this->hasMany(Page::class);
    }

    public function mainPages()
    {
        return $this->hasMany(MainPage::class);
    }

    public function resources()
    {
        return $this->hasMany(Resource::class);
    }

    public function users()
    {
        return $this->hasManyDeepFromRelations($this->institutions(), (new Institution)->duties(), (new Duty)->current_users());
    }

    public function reservations()
    {
        return $this->hasManyDeepFromRelations($this->users(), (new User)->reservations());
    }

    public function tenant()
    {
        return $this;
    }
}
