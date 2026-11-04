<?php

namespace App\Models\Traits;

/**
 * Keeps a model's Public* search mirror in step with it. Nothing ever saves the mirror, so
 * its own Scout observer never fires; the parent's lifecycle pushes it instead.
 */
trait HasPublicSearchMirror
{
    /**
     * The mirror subclasses the model using this trait.
     *
     * @return class-string<self>
     */
    abstract protected static function publicSearchMirror(): string;

    public static function bootHasPublicSearchMirror(): void
    {
        // restore() saves, and a force delete fires `deleted`, so these two cover every write.
        static::saved(fn (self $model) => $model->syncPublicSearchIndex());
        static::deleted(fn (self $model) => static::removeFromPublicSearch($model->getKey()));
    }

    /**
     * Index the mirror if it should be public now, otherwise remove it. Call this whenever
     * something outside the row (a relation, a related record) changes that answer.
     */
    public function syncPublicSearchIndex(): void
    {
        $mirrorClass = static::publicSearchMirror();
        $mirror = $mirrorClass::query()->whereKey($this->getKey())->first();

        if ($mirror?->shouldBeSearchable()) {
            $mirror->searchable();

            return;
        }

        static::removeFromPublicSearch($this->getKey());
    }

    /**
     * Works for a key whose row is already gone, which a lookup through the mirror cannot.
     */
    public static function removeFromPublicSearch(int|string $key): void
    {
        $mirrorClass = static::publicSearchMirror();
        $mirror = new $mirrorClass;
        $mirror->setAttribute($mirror->getKeyName(), $key);
        $mirror->unsearchable();
    }
}
