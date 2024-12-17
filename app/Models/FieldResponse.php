<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class FieldResponse extends Model
{
    /** @use HasFactory<\Database\Factories\FieldResponseFactory> */
    use HasFactory;

    protected $fillable = [
        'response',
    ];

    protected $casts = [
        'response' => 'array',
    ];

    public function registration()
    {
        return $this->belongsTo(Registration::class);
    }

    public function formField()
    {
        return $this->belongsTo(FormField::class);
    }
}
