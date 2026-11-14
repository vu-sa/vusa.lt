<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Where a knowledge item sits in a structure graph (`App\Support\StructureGraphs`).
 *
 * @property int $id
 * @property string $knowledge_item_id
 * @property string $graph
 * @property string|null $node
 * @property int $order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read KnowledgeItem $item
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeItemStructureNode newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeItemStructureNode newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|KnowledgeItemStructureNode query()
 *
 * @mixin \Eloquent
 */
#[Fillable(['knowledge_item_id', 'graph', 'node', 'order'])]
class KnowledgeItemStructureNode extends Model
{
    protected function casts(): array
    {
        return ['order' => 'integer'];
    }

    /** @return BelongsTo<KnowledgeItem, $this> */
    public function item(): BelongsTo
    {
        return $this->belongsTo(KnowledgeItem::class, 'knowledge_item_id');
    }
}
