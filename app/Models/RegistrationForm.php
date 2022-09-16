<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Registration;

class RegistrationForm extends Model
{
    use HasFactory;

    public function registrations()
    {
        return $this->hasMany(Registration::class);
    }
    
}
