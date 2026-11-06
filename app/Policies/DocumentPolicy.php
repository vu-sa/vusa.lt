<?php

namespace App\Policies;

use App\Enums\ModelEnum;
use App\Models\Document;
use App\Models\User;
use App\Services\ModelAuthorizer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Policy for Document model authorization
 */
class DocumentPolicy extends ModelPolicy
{
    /**
     * These models belong to a single tenant through a `tenant` relation.
     */
    #[\Override]
    protected bool $hasManyTenants = false;

    /**
     * Initialize policy with model name
     */
    public function __construct(ModelAuthorizer $authorizer)
    {
        parent::__construct($authorizer);
        $this->pluralModelName = Str::plural(ModelEnum::DOCUMENT->label());
    }

    /**
     * Documents are public, so every admin may browse the collection; managing them is gated
     * by `create` / `update` / `delete`.
     */
    #[\Override]
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * `.own` lets a body inside a padalinys (Revizijos komisija) pick and discover files too;
     * publishing each one is still checked against its institution.
     */
    #[\Override]
    public function create(User $user): bool
    {
        return parent::create($user) || $this->authorizer->allows($user, 'documents.create.own');
    }

    #[\Override]
    public function view(User $user, Model $model): bool
    {
        // A file nobody has published yet is a manager's decision, not something to browse.
        if ($model instanceof Document && ! $model->isPublished()) {
            return $this->update($user, $model);
        }

        return parent::view($user, $model);
    }

    /**
     * Publishing or hiding is an edit of the document within the manager's padaliniai.
     */
    public function publish(User $user, Document $document): bool
    {
        return $this->update($user, $document);
    }
}
