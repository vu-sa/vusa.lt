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
     * @throws ValidationException when a file is not an archive document or has no Padalinys
     * @throws AuthorizationException when the user may not publish one of them
     */
    public static function execute(array $items, User $user): Collection
    {
        $discovery = app(SharepointDocumentDiscovery::class);
        $documents = new Collection(array_map(function (array $item) use ($discovery): Document {
            try {
                return $discovery->importPicked($item['site_id'], $item['list_id'], $item['list_item_unique_id']);
            } catch (PickedDocumentException $e) {
                throw ValidationException::withMessages(['documents' => $e->getMessage()]);
            }
        }, $items));

        $documents = $documents->unique('id')->values()->load('institution');

        // Without a Padalinys only `*` scope may publish it; say what to fix instead of a bare refusal.
        $unassigned = $documents->filter(fn (Document $document): bool => $document->institution_id === null && ! $user->can('publish', $document));

        if ($unassigned->isNotEmpty()) {
            throw ValidationException::withMessages(['documents' => __('messages.document.picked_no_institution', [
                'titles' => $unassigned->map(fn (Document $document): string => $document->title ?: $document->name)->join(', '),
            ])]);
        }

        if ($documents->contains(fn (Document $document): bool => ! $user->can('publish', $document))) {
            throw new AuthorizationException;
        }

        return $documents;
    }
}
