<?php

namespace App\Http\Traits;

use App\Contracts\GuardsForceDelete;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;

/**
 * Shared restore / permanent-delete behaviour for admin controllers of
 * soft-deletable models.
 *
 * Controllers keep a thin, route-model-bound wrapper so implicit binding and
 * type hints stay explicit, and delegate the authorization, the action and the
 * flash redirect to this trait:
 *
 * ```php
 * public function restore(News $news): RedirectResponse
 * {
 *     return $this->restoreModel($news);
 * }
 * ```
 *
 * Both routes must declare `->withTrashed()` on the binding, otherwise Laravel's
 * implicit resolution applies the SoftDeletingScope and the trashed record 404s.
 */
trait HandlesSoftDeletes
{
    /**
     * Restore a soft-deleted model.
     *
     * Authorized by the model's `restore` policy method, which reuses the existing
     * `{resource}.delete.{scope}` permission — restoring is the inverse of deleting,
     * so it deliberately does not introduce a permission of its own.
     *
     * Restoring can still fail at the database layer — a unique slot the record used
     * to own may have been taken while it sat in the trash — so it gets the same
     * QueryException treatment as permanent deletion rather than a 500.
     */
    protected function restoreModel(Model $model, ?string $message = null): RedirectResponse
    {
        $this->assertSoftDeletable($model);

        $this->authorize('restore', $model);

        // Restoring an already-active record is a no-op rather than an error, so a
        // double-submitted form does not surface a confusing failure.
        if (method_exists($model, 'trashed') && $model->trashed()) {
            try {
                $model->restore();
            } catch (QueryException $exception) {
                report($exception);

                return back()->with('error', __('trash.restore_conflict'));
            }
        }

        return back()->with('success', $message ?? __('trash.restored'));
    }

    /**
     * Permanently delete a soft-deleted model.
     *
     * Gated by the dedicated `{resource}.forceDelete.{scope}` permission. Only records
     * that are already trashed may be force-deleted: permanent deletion must always be
     * a deliberate second step taken from the trash view, never a one-click action on a
     * live record.
     *
     * Models that own records which must outlive them implement {@see GuardsForceDelete}
     * and are refused with an explanation. The QueryException fallback covers references
     * no model declares: a restricting foreign key must surface as an error toast, never
     * as a 500.
     */
    protected function forceDeleteModel(Model $model, ?string $message = null): RedirectResponse
    {
        $this->assertSoftDeletable($model);

        $this->authorize('forceDelete', $model);

        abort_unless($model->trashed(), 403, __('trash.must_be_deleted_first'));

        if ($model instanceof GuardsForceDelete && ($reason = $model->forceDeleteBlockedReason()) !== null) {
            return back()->with('error', $reason);
        }

        try {
            $model->forceDelete();
        } catch (QueryException $exception) {
            report($exception);

            return back()->with('error', __('trash.blocked.has_related_records'));
        }

        return back()->with('success', $message ?? __('trash.permanently_deleted'));
    }

    /**
     * Guard against wiring these actions up to a model that is not soft-deletable,
     * which would otherwise fail confusingly at the database layer.
     */
    private function assertSoftDeletable(Model $model): void
    {
        if (! in_array(SoftDeletes::class, class_uses_recursive($model), true)) {
            throw new \LogicException($model::class.' does not use the SoftDeletes trait.');
        }
    }
}
