<?php

namespace App\Contracts;

use App\Models\Comment;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * A model that hosts a discussion thread. Satisfied by the
 * {@see \App\Models\Traits\HasComments} trait.
 */
interface Commentable
{
    public function comments(): MorphMany;

    public function rootComments(): MorphMany;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function comment(string $body, ?string $parentId = null, array $attributes = []): Comment;
}
