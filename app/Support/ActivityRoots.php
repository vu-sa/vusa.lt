<?php

namespace App\Support;

use App\Models\AgendaItemNote;
use App\Models\Content;
use App\Models\ContentPart;
use App\Models\Duty;
use App\Models\Pivots\AgendaItem;
use App\Models\Vote;

/**
 * Maps a child model class to the relation name(s) that lead to its "root" for
 * activity log roll-up purposes -- e.g. a Vote's root is its AgendaItem's
 * Meeting, so editing a vote surfaces in the meeting's activity feed. A model
 * absent from PARENTS is its own root.
 *
 * A value may be a single relation name, or a list of candidate relation
 * names tried in order -- needed for Content, whose owner (News, Page, or
 * Tenant) has no discriminator column; see App\Services\ActivityRootResolver.
 *
 * Kept in sync with the backfill in
 * database/migrations/2026_08_02_090000_add_root_subject_to_activity_log.php
 * and read at runtime by App\Services\ActivityRootResolver.
 */
class ActivityRoots
{
    /**
     * @var array<class-string, string|list<string>>
     */
    public const PARENTS = [
        Vote::class => 'agendaItem',
        AgendaItem::class => 'meeting',
        AgendaItemNote::class => 'agendaItem',
        Duty::class => 'institution',
        ContentPart::class => 'content',
        // Inverse hasOne on content_id, ambiguous between three owners -- see
        // App\Models\Content::news()/page()/tenant().
        Content::class => ['news', 'page', 'tenant'],
    ];

    /**
     * Hard cap on how far up the chain the resolver will walk, as a guard
     * against an accidental cycle in PARENTS.
     */
    public const MAX_DEPTH = 5;
}
