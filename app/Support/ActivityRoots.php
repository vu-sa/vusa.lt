<?php

namespace App\Support;

use App\Models\AgendaItemNote;
use App\Models\Content;
use App\Models\ContentPart;
use App\Models\Duty;
use App\Models\InstitutionLink;
use App\Models\InstitutionTypeLink;
use App\Models\MailingListExclusion;
use App\Models\MailingListSource;
use App\Models\Pivots\AgendaItem;
use App\Models\Step;
use App\Models\TenantHomepageContent;
use App\Models\Vote;

/** Child activities follow these relations to their canonical record's history. */
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
        // App\Models\Content::news()/page()/tenantHomepageContent().
        Content::class => ['news', 'page', 'tenantHomepageContent'],
        TenantHomepageContent::class => 'tenant',
        // Goals pilot: tried in order, so a step on both shows in its goal's feed.
        Step::class => ['goal', 'problem'],
        InstitutionLink::class => 'source',
        InstitutionTypeLink::class => 'source',
        MailingListSource::class => 'mailingList',
        MailingListExclusion::class => 'mailingList',
    ];

    /**
     * Hard cap on how far up the chain the resolver will walk, as a guard
     * against an accidental cycle in PARENTS.
     */
    public const MAX_DEPTH = 5;
}
