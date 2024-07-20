<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    use HasFactory;

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

    public function resources()
    {
        return $this->hasMany(Resource::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
