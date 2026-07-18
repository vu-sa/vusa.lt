<?php

namespace App\Support;

use App\Models\Institution;
use App\Models\Meeting;
use App\Models\Pivots\AgendaItem;
use App\Models\Problem;
use Illuminate\Database\Eloquent\Model;

/**
 * Central allowlist mapping workspace-linkable aliases (used in API payloads)
 * to their model classes. Resolving polymorphic types from user-supplied
 * strings must always go through this allowlist so arbitrary classes can
 * never be instantiated.
 */
class WorkspaceLinkables
{
    /**
     * @var array<string, class-string<Model>>
     */
    public const TYPES = [
        'meeting' => Meeting::class,
        'agendaItem' => AgendaItem::class,
        'problem' => Problem::class,
        'institution' => Institution::class,
    ];

    /**
     * @return class-string<Model>|null
     */
    public static function classFor(string $type): ?string
    {
        return self::TYPES[$type] ?? null;
    }

    public static function aliasFor(?Model $model): ?string
    {
        if ($model === null) {
            return null;
        }

        return array_search($model::class, self::TYPES, true) ?: null;
    }

    /**
     * Resolve a linkable model instance from an alias + id, or null if the
     * alias is unknown or the model does not exist.
     */
    public static function resolve(string $type, string $id): ?Model
    {
        $class = self::classFor($type);

        if ($class === null) {
            return null;
        }

        return $class::query()->find($id);
    }
}
