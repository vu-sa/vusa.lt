<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

trait HasTypeHierarchy
{
    public function parent(): BelongsTo
    {
        return $this->belongsTo(static::class, 'parent_id');
    }

    /** @return HasMany<static, $this> */
    public function descendants(): HasMany
    {
        return $this->hasMany(static::class, 'parent_id');
    }

    public function recursiveDescendants(): HasMany
    {
        return $this->descendants()->with('recursiveDescendants');
    }

    public function recursiveParent(): BelongsTo
    {
        return $this->parent()->with('recursiveParent');
    }

    /** @return Collection<int, self> */
    public function getDescendantsAndSelf(bool $withTrashed = false): Collection
    {
        $result = [$this];

        foreach (($withTrashed ? $this->descendants()->withTrashed()->get() : $this->descendants) as $descendant) {
            $result = [...$result, ...$descendant->getDescendantsAndSelf($withTrashed)->all()];
        }

        return new Collection($result)->unique('id')->values();
    }

    public function getParentsAndSelf(): Collection
    {
        $result = new Collection;
        $type = $this;

        while ($type !== null && ! $result->contains('id', $type->getKey())) {
            $result->push($type);
            $type = $type->parent;
        }

        return $result->reverse()->values();
    }
}
