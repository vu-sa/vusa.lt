<?php

namespace App\Actions;

use App\Models\Activity;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * The last few records a user changed, newest first — Pradžia's "Neseniai redaguota" (O20).
 *
 * Reads the activity log by root subject, so a vote edit surfaces as its meeting. Records the
 * user can no longer view, or that are deleted, are skipped rather than linking to a 403.
 */
class GetRecentlyEditedRecords
{
    /**
     * Root types that have a page to open, and the route that opens it. Content is edited in
     * its editor (O4), work objects open their record.
     *
     * @var array<string, string>
     */
    public const ROUTES = [
        'meeting' => 'meetings.show',
        'institution' => 'institutions.show',
        'duty' => 'duties.show',
        'user' => 'users.show',
        'form' => 'forms.show',
        'problem' => 'problems.show',
        'reservation' => 'reservations.show',
        'resource' => 'resources.show',
        'document' => 'documents.show',
        'news' => 'news.edit',
        'page' => 'pages.edit',
        'calendar' => 'calendar.edit',
    ];

    /**
     * @param  list<string>|null  $types  Restrict to these root types, e.g. a workspace overview's own entities.
     * @return Collection<int, array{type: string, id: string, title: string, href: string, changed_at: string}>
     */
    public static function execute(User $user, int $limit = 3, ?array $types = null): Collection
    {
        // A handful of activities usually share a root (one meeting, many vote edits), so
        // over-read and dedupe instead of paging until $limit distinct roots turn up.
        $activities = Activity::query()
            ->causedBy($user)
            ->whereIn('root_subject_type', $types === null ? array_keys(self::ROUTES) : array_intersect($types, array_keys(self::ROUTES)))
            ->whereNotNull('root_subject_id')
            ->latest()
            ->limit($limit * 15)
            ->with('rootSubject')
            ->get()
            ->unique(fn (Activity $activity): string => $activity->root_subject_type.':'.$activity->root_subject_id);

        /** @var Collection<int, array{type: string, id: string, title: string, href: string, changed_at: string}> $records */
        $records = new Collection;

        foreach ($activities as $activity) {
            $root = $activity->rootSubject;

            if (! $root instanceof Model || ! $user->can('view', $root)) {
                continue;
            }

            $records->push([
                'type' => $activity->root_subject_type,
                'id' => (string) $root->getKey(),
                'title' => self::titleOf($root),
                'href' => route(self::ROUTES[$activity->root_subject_type], $root),
                'changed_at' => $activity->created_at->toISOString(),
            ]);

            if ($records->count() === $limit) {
                break;
            }
        }

        return $records;
    }

    private static function titleOf(Model $root): string
    {
        $title = $root->getAttribute('title') ?? $root->getAttribute('name');

        return is_string($title) && $title !== '' ? $title : (string) $root->getKey();
    }
}
