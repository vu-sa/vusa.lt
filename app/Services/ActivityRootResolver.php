<?php

namespace App\Services;

use App\Models\Activity;
use App\Support\ActivityRoots;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Throwable;

/**
 * Resolves an activity's subject up to its "root" (see App\Support\ActivityRoots)
 * and stamps root_subject_type/root_subject_id on the activity. Registered as a
 * singleton (see App\Providers\ActivityLogServiceProvider) so the per-request
 * caches actually pay off: saving several votes on one agenda item costs two
 * root-resolution queries total, not two per vote -- the second cache below is
 * what makes that true even when each vote is a distinct model instance
 * without its agendaItem relation preloaded.
 *
 * root_subject_* is denormalised, so if an AgendaItem is ever moved between
 * Meetings (or a Duty between Institutions), activities logged before the move
 * keep pointing at the old root. That reflects which tree the change actually
 * happened under at the time, so it is accepted rather than treated as a bug.
 */
class ActivityRootResolver
{
    /**
     * Resolved root per subject, keyed by "{class}:{id}".
     *
     * @var array<string, array{0: string, 1: string}>
     */
    private array $memo = [];

    /**
     * Loaded parent models per foreign key value, keyed by "{class}:{id}".
     * Distinct child instances that share a parent (e.g. several votes on one
     * agenda item) reuse the same parent lookup instead of re-querying it.
     *
     * @var array<string, Model>
     */
    private array $parentCache = [];

    public function stamp(Activity $activity): void
    {
        $subject = $activity->subject ?? null;

        if (! $subject instanceof Model) {
            return;
        }

        [$type, $id] = $this->resolve($subject);

        $activity->root_subject_type = $type;
        $activity->root_subject_id = $id;
    }

    /**
     * @return array{0: string, 1: string}
     */
    public function resolve(Model $subject): array
    {
        $key = $this->memoKey($subject);

        return $this->memo[$key] ??= $this->walk($subject, 0);
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function walk(Model $model, int $depth): array
    {
        $relationNames = ActivityRoots::PARENTS[$model::class] ?? null;

        if ($relationNames === null || $depth >= ActivityRoots::MAX_DEPTH) {
            return $this->asSelf($model);
        }

        $parent = null;

        // Usually a single relation name; Content is the exception, whose
        // owner (News, Page, or Tenant) has no discriminator column, so each
        // candidate is tried in turn until one resolves.
        foreach ((array) $relationNames as $relationName) {
            $parent = $this->loadParent($model, $relationName);

            if ($parent instanceof Model) {
                break;
            }
        }

        if (! $parent instanceof Model) {
            // Missing/soft-deleted parent -- e.g. mid cascade-delete of a
            // Meeting, where an AgendaItem's row may already be gone by the
            // time a Vote's "deleted" activity resolves its root -- or an
            // orphan Content row with no owner. Fall back to the subject as
            // its own root rather than throwing.
            return $this->asSelf($model);
        }

        $parentKey = $this->memoKey($parent);

        return $this->memo[$parentKey] ??= $this->walk($parent, $depth + 1);
    }

    private function loadParent(Model $model, string $relationName): ?Model
    {
        try {
            if ($model->relationLoaded($relationName)) {
                $parent = $model->getRelation($relationName);

                return $parent instanceof Model ? $parent : null;
            }

            $relation = $model->{$relationName}();

            // The resolver only needs the parent's own attributes to keep
            // walking the chain, never its eager-loaded relations (e.g.
            // Content::$with = ['parts']) -- drop them to avoid a wasted query.
            $relation->withOnly([]);

            // All current ActivityRoots::PARENTS entries are BelongsTo, which
            // is what makes the parent cache below possible without a query:
            // the foreign key value is already sitting on $model's attributes.
            if (! $relation instanceof BelongsTo) {
                $parent = $relation->first();

                return $parent instanceof Model ? $parent : null;
            }

            $foreignKeyValue = $model->getAttribute($relation->getForeignKeyName());

            if ($foreignKeyValue === null) {
                return null;
            }

            $cacheKey = $relation->getRelated()::class.':'.$foreignKeyValue;

            if (isset($this->parentCache[$cacheKey])) {
                return $this->parentCache[$cacheKey];
            }

            $parent = $relation->first();

            if ($parent instanceof Model) {
                $this->parentCache[$cacheKey] = $parent;
            }

            return $parent;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function asSelf(Model $model): array
    {
        return [$model->getMorphClass(), (string) $model->getKey()];
    }

    private function memoKey(Model $model): string
    {
        return $model::class.':'.$model->getKey();
    }
}
