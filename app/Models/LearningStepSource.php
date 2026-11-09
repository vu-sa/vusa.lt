<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property int $id
 * @property string $learning_step_id
 * @property string $locale
 * @property int|null $document_id
 * @property string|null $url
 * @property-read LearningStep $step
 * @property-read Document|null $document
 */
#[Fillable(['learning_step_id', 'locale', 'document_id', 'url'])]
class LearningStepSource extends Model
{
    use HasFactory;

    /** @return BelongsTo<LearningStep, $this> */
    public function step(): BelongsTo
    {
        return $this->belongsTo(LearningStep::class, 'learning_step_id');
    }

    /** @return BelongsTo<Document, $this> */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
}
