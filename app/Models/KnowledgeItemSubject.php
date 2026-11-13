<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * What a knowledge page is about. Unlike an audience (who should read it), a subject only links
 * the page and the record both ways.
 *
 * @property int $id
 * @property string $knowledge_item_id
 * @property string $subject_type
 * @property string $subject_id
 * @property-read KnowledgeItem $item
 * @property-read Institution|InstitutionType|DutyType|KnowledgeItem|null $subject
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeItemSubject newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeItemSubject newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeItemSubject query()
 *
 * @mixin \Eloquent
 */
#[Fillable(['knowledge_item_id', 'subject_type', 'subject_id'])]
#[WithoutTimestamps]
class KnowledgeItemSubject extends Model
{
    /** Morph aliases a page may be about; a `knowledge_item` subject is always a term. */
    public const array SUBJECT_TYPES = ['institution', 'institution_type', 'duty_type', 'knowledge_item'];

    /** @return BelongsTo<KnowledgeItem, $this> */
    public function item(): BelongsTo
    {
        return $this->belongsTo(KnowledgeItem::class, 'knowledge_item_id');
    }

    /** @return MorphTo<contravariant Institution|InstitutionType|DutyType|KnowledgeItem, $this> */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
