<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $registration_id
 * @property int $form_field_id
 * @property string $response
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read \App\Models\FormField $formField
 * @property-read \App\Models\Registration $registration
 * @method static \Database\Factories\FieldResponseFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FieldResponse newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FieldResponse newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FieldResponse query()
 * @mixin \Eloquent
 */
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
