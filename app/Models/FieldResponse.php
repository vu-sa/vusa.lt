<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FieldResponse extends Model
{
    /** @use HasFactory<\Database\Factories\FieldResponseFactory> */
    use HasFactory;

    protected $fillable = [
        'response',
    ];

    protected $casts = [
        // Note: Removed 'response' => 'array' cast as text/email fields should store strings
        // For field types that need arrays (checkboxes, multi-select), handle in controller
    ];

    public function registration()
    {
        return $this->belongsTo(Registration::class);
    }

    public function formField(): BelongsTo
    {
        return $this->belongsTo(FormField::class);
    }
}
