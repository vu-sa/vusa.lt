<?php

namespace App\Models\Traits;

use App\Events\SearchRelationChanged;
use App\Models\User;
use App\Support\AuditedRelations;
use App\Support\Dutiables\DutyAssignmentActor;
use Closure;
use Illuminate\Support\Collection;
use InvalidArgumentException;

/**
 * Logs a `relation_updated` activity around sync()/attach()/detach() calls on
 * an allowlisted relation (see App\Support\AuditedRelations). Eloquent fires
 * no model events for these -- they go through newPivotStatement(), not
 * save() -- so without this wrapper the change is invisible in the activity
 * log no matter what LogOptions the model declares.
 *
 * A trait rather than a post-hoc service: the "before" snapshot has to be
 * taken *before* the mutation runs, so wrapping the call is the only way to
 * get the ordering right without duplicating it at every call site.
 */
trait LogsRelationshipChanges
{
    public function syncAudited(string $relation, mixed $ids, bool $detaching = true): mixed
    {
        return $this->auditRelationChange($relation, fn () => $this->{$relation}()->sync($ids, $detaching));
    }

    public function attachAudited(string $relation, mixed $ids, array $attributes = []): mixed
    {
        return $this->auditRelationChange($relation, fn () => $this->{$relation}()->attach($ids, $attributes));
    }

    public function detachAudited(string $relation, mixed $ids = null): mixed
    {
        return $this->auditRelationChange($relation, fn () => $this->{$relation}()->detach($ids));
    }

    /**
     * Runs $mutation against $relation, diffing before/after membership and
     * logging one `relation_updated` activity iff something actually changed.
     *
     * $relation must be a literal string from a fixed call site, never
     * request input -- this rejects names missing from AuditedRelations.
     */
    public function auditRelationChange(string $relation, Closure $mutation): mixed
    {
        $finish = $this->prepareRelationAudit($relation);
        $result = $mutation();
        $finish();

        return $result;
    }

    public function prepareRelationAudit(string $relation): Closure
    {
        $config = AuditedRelations::configFor($this, $relation);

        if ($config === null) {
            throw new InvalidArgumentException(sprintf('%s::%s is not an audited relation.', static::class, $relation));
        }

        $related = $this->{$relation}()->getRelated();
        // Qualified column names: the pivot table joined in for this relation
        // may itself have an "id" column, which makes an unqualified pluck('id')
        // ambiguous.
        $display = $related->qualifyColumn($config['display']);
        $keyName = $related->getQualifiedKeyName();
        $snapshot = fn () => $this->{$relation}()->pluck($display, $keyName);
        $before = $snapshot();
        $actor = DutyAssignmentActor::scoped();

        return fn () => $this->logRelationChange($relation, $before, $snapshot(), $actor);
    }

    private function logRelationChange(string $relation, Collection $before, Collection $after, User|false|null $actor): void
    {
        $attachedIds = $after->keys()->diff($before->keys());
        $detachedIds = $before->keys()->diff($after->keys());

        if ($attachedIds->isEmpty() && $detachedIds->isEmpty()) {
            return;
        }

        $activity = activity()
            ->performedOn($this)
            ->event('relation_updated')
            ->withProperties([
                'relation' => $relation,
                'attached' => $attachedIds->map(fn ($id) => ['id' => (string) $id, 'label' => (string) $after->get($id)])->values()->all(),
                'detached' => $detachedIds->map(fn ($id) => ['id' => (string) $id, 'label' => (string) $before->get($id)])->values()->all(),
            ]);

        $activity = match ($actor) {
            null => $activity,
            false => $activity->causedByAnonymous(),
            default => $activity->causedBy($actor),
        };
        $activity->log('relation_updated');

        SearchRelationChanged::dispatch($this, $relation);
    }
}
