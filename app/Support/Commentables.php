<?php

namespace App\Support;

use App\Contracts\Commentable;
use App\Models\Duty;
use App\Models\Form;
use App\Models\Institution;
use App\Models\KnowledgeItem;
use App\Models\LearningStep;
use App\Models\Meeting;
use App\Models\Pivots\AgendaItem;
use App\Models\Problem;
use App\Models\Reservation;
use App\Models\SupportRequest;
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
        'knowledgeItem' => KnowledgeItem::class,
        'learningStep' => LearningStep::class,
        'meeting' => Meeting::class,
        'agendaItem' => AgendaItem::class,
        'institution' => Institution::class,
        'duty' => Duty::class,
        'form' => Form::class,
        'problem' => Problem::class,
        'reservation' => Reservation::class,
        'supportRequest' => SupportRequest::class,
    ];

    public const CAPABILITIES = [
        'knowledgeItem' => ['layout' => 'topics', 'topics' => true, 'polls' => true, 'images' => true],
        'learningStep' => ['layout' => 'topics', 'topics' => true, 'polls' => true, 'images' => true],
        'meeting' => ['layout' => 'comments', 'topics' => true, 'polls' => true, 'images' => true],
        'agendaItem' => ['layout' => 'comments', 'topics' => true, 'polls' => true, 'images' => true],
        'institution' => ['layout' => 'comments', 'topics' => true, 'polls' => true, 'images' => true],
        'duty' => ['layout' => 'comments', 'topics' => true, 'polls' => true, 'images' => true],
        'form' => ['layout' => 'comments', 'topics' => true, 'polls' => true, 'images' => true],
        'problem' => ['layout' => 'comments', 'topics' => true, 'polls' => true, 'images' => true],
        'reservation' => ['layout' => 'comments', 'topics' => true, 'polls' => true, 'images' => true],
        'supportRequest' => ['layout' => 'conversation', 'topics' => false, 'polls' => false, 'images' => true],
    ];

    /** @return array{layout: string, topics: bool, polls: bool, images: bool} */
    public static function capabilitiesFor(string $alias): array
    {
        return self::CAPABILITIES[$alias] ?? ['layout' => 'comments', 'topics' => true, 'polls' => true, 'images' => true];
    }

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
