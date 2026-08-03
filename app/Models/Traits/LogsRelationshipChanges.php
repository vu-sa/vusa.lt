<?php

namespace App\Models\Traits;

use App\Support\AuditedRelations;
use Closure;
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
     * request input -- AuditedRelations::configFor() throws otherwise, which
     * is what makes that a structural guarantee rather than a convention.
     */
    public function auditRelationChange(string $relation, Closure $mutation): mixed
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

        $before = $this->{$relation}()->pluck($display, $keyName);

        $result = $mutation();

        $after = $this->{$relation}()->pluck($display, $keyName);

        $attachedIds = $after->keys()->diff($before->keys());
        $detachedIds = $before->keys()->diff($after->keys());

        if ($attachedIds->isEmpty() && $detachedIds->isEmpty()) {
            return $result;
        }

        activity()
            ->performedOn($this)
            ->event('relation_updated')
            ->withProperties([
                'relation' => $relation,
                'attached' => $attachedIds->map(fn ($id) => ['id' => (string) $id, 'label' => (string) $after->get($id)])->values()->all(),
                'detached' => $detachedIds->map(fn ($id) => ['id' => (string) $id, 'label' => (string) $before->get($id)])->values()->all(),
            ])
            ->log('relation_updated');

        return $result;
    }
}
