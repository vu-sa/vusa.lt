<?php

namespace App\Support;

use App\Contracts\Commentable;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\Pivots\AgendaItem;
use App\Models\Reservation;
use App\Models\SharepointFile;
use Illuminate\Database\Eloquent\Model;

/**
 * Central allowlist mapping public commentable aliases (used in API paths and
 * broadcast channel names) to their model classes. Resolving polymorphic types
 * from user-supplied strings must always go through this allowlist so arbitrary
 * classes can never be instantiated.
 */
class Commentables
{
    /**
     * @var array<string, class-string<Model&Commentable>>
     */
    public const TYPES = [
        'meeting' => Meeting::class,
        'agendaItem' => AgendaItem::class,
        'institution' => Institution::class,
        'reservation' => Reservation::class,
        'sharepointFile' => SharepointFile::class,
    ];

    /**
     * @return class-string<Model&Commentable>|null
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
     * Resolve a commentable model instance from an alias + id, or null if the
     * alias is unknown or the model does not exist.
     *
     * @return (Model&Commentable)|null
     */
    public static function resolve(string $type, string $id): ?Commentable
    {
        $class = self::classFor($type);

        if ($class === null) {
            return null;
        }

        $model = $class::query()->find($id);

        return $model instanceof Commentable ? $model : null;
    }
}
