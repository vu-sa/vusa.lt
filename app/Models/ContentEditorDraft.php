<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;

class ContentEditorDraft extends Model
{
    use MassPrunable;

    protected $fillable = ['user_id', 'kind', 'identity', 'snapshot', 'revision'];

    protected function casts(): array
    {
        return ['snapshot' => 'array', 'revision' => 'integer'];
    }

    public function prunable(): Builder
    {
        return static::query()->where('updated_at', '<', now()->subDays(30));
    }
}
