<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Padalinys extends Model
{
    protected $table = 'padaliniai';

    public function banners()
    {
        return $this->hasMany(Banner::class, 'padalinys_id');
    }

    public function calendar()
    {
        return $this->hasMany(Calendar::class, 'padalinys_id');
    }

    public function duties()
    {
        return $this->hasManyThrough(Duty::class, DutyInstitution::class, 'padalinys_id', 'institution_id');
    }

    public function institutions()
    {
        return $this->hasMany(DutyInstitution::class, 'padalinys_id');
    }    

    public function news()
    {
        return $this->hasMany(News::class, 'padalinys_id');
    }

    public function pages()
    {
        return $this->hasMany(Page::class, 'padalinys_id');
    }

    public function users()
    {
        return $this->hasMany(User::class, 'padalinys_id');
    }
}
