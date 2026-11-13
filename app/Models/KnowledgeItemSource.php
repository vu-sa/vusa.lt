<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $knowledge_item_id
 * @property string $locale
 * @property int|null $document_id
 * @property string|null $url
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Document|null $document
 * @property-read KnowledgeItem $item
 *
 * @method static \Database\Factories\KnowledgeItemSourceFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeItemSource newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeItemSource newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeItemSource query()
 *
 * @mixin \Eloquent
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
