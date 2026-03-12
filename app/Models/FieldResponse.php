<?php

namespace App\Models;

use Database\Factories\FieldResponseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $registration_id
 * @property int $form_field_id
 * @property array<array-key, mixed> $response
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read FormField $formField
 * @property-read Registration $registration
 *
 * @method static \Database\Factories\FieldResponseFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FieldResponse newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FieldResponse newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|FieldResponse query()
 *
 * @mixin \Eloquent
 */
class FieldResponse extends Model
{
    /** @use HasFactory<FieldResponseFactory> */
    use HasFactory;

    protected $fillable = [
        'response',
    ];

    protected function casts(): array
    {
        return [
            'response' => 'array',
        ];
    }

    /**
     * Get the actual value from the response structure.
     */
    public function getValue(): mixed
    {
        return $this->response['value'] ?? null;
    }

    /**
     * Check if the response has a value.
     */
    public function hasValue(): bool
    {
        return ! empty($this->response['value']);
    }

    public function registration()
    {
        return $this->belongsTo(Registration::class);
    }

    public function formField(): BelongsTo
    {
        return $this->belongsTo(FormField::class);
    }
}
