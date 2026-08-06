<?php

namespace App\Support;

use App\Models\Duty;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Allowlist of BelongsToMany/MorphToMany relations whose sync/attach/detach
 * calls are worth logging as a `relation_updated` activity -- see
 * App\Models\Traits\LogsRelationshipChanges. Eloquent fires no model events
 * for these operations, so without this the change is invisible in the log.
 *
 * The allowlist is what makes LogsRelationshipChanges::auditRelationChange()
 * safe to call with a relation name: $relation must match a key here, so it
 * can never dispatch an arbitrary method built from request input (see
 * AGENTS.md's "never dispatch a method name built from request input" rule).
 */
class AuditedRelations
{
    /**
     * owner class => relation name => the related model's display attribute
     * (used to label attached/detached items -- see LogsRelationshipChanges).
     *
     * @var array<class-string, array<string, array{display: string}>>
     */
    public const RELATIONS = [
        Duty::class => [
            'users' => ['display' => 'name'],
        ],
        Meeting::class => [
            'institutions' => ['display' => 'name'],
        ],
        Institution::class => [
            'types' => ['display' => 'title'],
        ],
        Reservation::class => [
            'users' => ['display' => 'name'],
            'resources' => ['display' => 'name'],
        ],
        // Granting somebody a duty is what puts them inside a tenant, and therefore
        // what confers authority over their record on that tenant's admins — the one
        // action most worth being able to reconstruct afterwards.
        //
        // current_duties, not duties: a revocation end-dates the pivot row rather than
        // deleting it, so `duties` membership never changes and nothing would be
        // logged — a half-audit that reads as a complete one.
        User::class => [
            'current_duties' => ['display' => 'name'],
        ],
    ];

    public static function isAudited(Model $model, string $relation): bool
    {
        return isset(self::RELATIONS[$model::class][$relation]);
    }

    /**
     * @return array{display: string}|null
     */
    public static function configFor(Model $model, string $relation): ?array
    {
        return self::RELATIONS[$model::class][$relation] ?? null;
    }
}
