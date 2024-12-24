<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Registration extends Model
{
    /** @use HasFactory<\Database\Factories\RegistrationFactory> */
    use HasFactory;

    public function form()
    {
        return $this->belongsTo(Form::class);
    }

    public function fieldResponses()
    {
        return $this->hasMany(FieldResponse::class);
    }
}
