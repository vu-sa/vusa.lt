<?php

namespace App\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * An audited relation (App\Support\AuditedRelations) actually changed. Eloquent fires no model
 * event for sync/attach/detach, so this is the one place other models' search documents that
 * embed the relation hear about it.
 */
class SearchRelationChanged
{
    use Dispatchable;

    public function __construct(public readonly Model $model, public readonly string $relation) {}
}
