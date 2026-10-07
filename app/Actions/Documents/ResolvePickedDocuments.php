<?php

namespace App\Actions\Documents;

use App\Models\Document;
use App\Models\User;
use App\Services\Documents\PickedDocumentException;
use App\Services\Documents\SharepointDocumentDiscovery;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

/**
 * The documents behind files chosen in SharePoint's own picker, so the old "pick a file" habit
 * lands on the same rows discovery keeps.
 */
class ResolvePickedDocuments
{
    /**
     * @param  list<array{site_id: string, list_id: string, list_item_unique_id: string}>  $items
     * @return Collection<int, Document>
     *
     * @throws ValidationException when a file is not an archive document
     * @throws AuthorizationException when the user may not publish one of them
     */
    public static function execute(array $items, User $user): Collection
    {
        $discovery = app(SharepointDocumentDiscovery::class);
        $documents = new Collection;

        foreach ($items as $item) {
            try {
                $documents->push($discovery->importPicked($item['site_id'], $item['list_id'], $item['list_item_unique_id']));
            } catch (PickedDocumentException $e) {
                throw ValidationException::withMessages(['documents' => $e->getMessage()]);
            }
        }

        $documents = $documents->unique('id')->values()->load('institution');

        if ($documents->contains(fn (Document $document): bool => ! $user->can('publish', $document))) {
            throw new AuthorizationException;
        }

        return $documents;
    }
}
