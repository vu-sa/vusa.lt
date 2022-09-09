<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Registration extends Model
{
    use HasFactory;

    protected $casts = [
        'data' => 'array',
        'updated_at' => 'datetime:Y-m-d H:i:s',
        'created_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function registrationForm()
    {
        return $this->belongsTo(RegistrationForm::class, 'registration_form_id', 'id');
    }
}
