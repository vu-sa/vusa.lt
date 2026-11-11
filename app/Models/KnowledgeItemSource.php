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
 * @property string $knowledge_item_id
 * @property string $locale
 * @property int|null $document_id
 * @property string|null $url
 * @property-read KnowledgeItem $item
 * @property-read Document|null $document
 */
#[Fillable(['knowledge_item_id', 'locale', 'document_id', 'url'])]
class KnowledgeItemSource extends Model
{
    use HasFactory;

    /** @return BelongsTo<KnowledgeItem, $this> */
    public function item(): BelongsTo
    {
        return $this->belongsTo(KnowledgeItem::class, 'knowledge_item_id');
    }

    /** @return BelongsTo<Document, $this> */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
}
