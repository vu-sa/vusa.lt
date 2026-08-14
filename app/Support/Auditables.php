<?php

namespace App\Support;

use App\Models\AgendaItemNote;
use App\Models\Banner;
use App\Models\Calendar;
use App\Models\ContentPart;
use App\Models\Document;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\Navigation;
use App\Models\News;
use App\Models\Page;
use App\Models\Pivots\AgendaItem;
use App\Models\Problem;
use App\Models\Reservation;
use App\Models\Tenant;
use App\Models\Type;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Database\Eloquent\Model;

/**
 * Central allowlist mapping public activity-log aliases (used in API paths) to
 * their model classes -- the same shape and intent as App\Support\Commentables.
 * Resolving polymorphic types from user-supplied strings must always go
 * through this allowlist so arbitrary classes can never be instantiated.
 *
 * TYPES lists the models a show page may request a root activity feed for.
 * SUBJECT_TYPES additionally lists descendants (Vote, AgendaItem,
 * AgendaItemNote) that only ever appear as a `subject` filter value inside a
 * root's feed, never as a feed's own root -- see App\Support\ActivityRoots.
 */
class Auditables
{
    /**
     * @var array<string, class-string<Model>>
     */
    public const TYPES = [
        'meeting' => Meeting::class,
        'institution' => Institution::class,
        'duty' => Duty::class,
        'problem' => Problem::class,
        'reservation' => Reservation::class,
        'news' => News::class,
        'page' => Page::class,
        'document' => Document::class,
        'calendar' => Calendar::class,
        'banner' => Banner::class,
        'navigation' => Navigation::class,
        'type' => Type::class,
        'user' => User::class,
        'tenant' => Tenant::class,
    ];

    /**
     * @var array<string, class-string<Model>>
     */
    public const SUBJECT_TYPES = self::TYPES + [
        'vote' => Vote::class,
        'agendaItem' => AgendaItem::class,
        'agendaItemNote' => AgendaItemNote::class,
        // Descendant-only: a body-text block edit is filterable inside its
        // News/Page/Tenant's feed, but ContentPart has no policy of its own
        // and is never requestable as a root -- see App\Support\ActivityRoots.
        'contentPart' => ContentPart::class,
    ];

    /**
     * @return class-string<Model>|null
     */
    public static function classFor(string $alias): ?string
    {
        return self::TYPES[$alias] ?? null;
    }

    /**
     * @return class-string<Model>|null
     */
    public static function subjectClassFor(string $alias): ?string
    {
        return self::SUBJECT_TYPES[$alias] ?? null;
    }

    /**
     * @param  string|class-string<Model>|Model|null  $model
     */
    public static function aliasFor(string|Model|null $model): ?string
    {
        if ($model === null) {
            return null;
        }

        $class = $model instanceof Model ? $model::class : $model;

        return array_search($class, self::SUBJECT_TYPES, true) ?: null;
    }

    /**
     * Resolve a root model instance from an alias + id, or null if the alias
     * is unknown or the model does not exist.
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
