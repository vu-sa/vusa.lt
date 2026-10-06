<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $user_id
 * @property string $kind
 * @property string $identity
 * @property array<array-key, mixed> $snapshot
 * @property int $revision
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static Builder<static>|ContentEditorDraft newModelQuery()
 * @method static Builder<static>|ContentEditorDraft newQuery()
 * @method static Builder<static>|ContentEditorDraft query()
 *
 * @mixin \Eloquent
 */
#[Fillable(['user_id', 'kind', 'identity', 'snapshot', 'revision'])]
class ContentEditorDraft extends Model
{
    use MassPrunable;

    protected function casts(): array
    {
        return ['snapshot' => 'array', 'revision' => 'integer'];
    }

    public function prunable(): Builder
    {
        return static::query()->where('updated_at', '<', now()->subDays(30));
    }
}
