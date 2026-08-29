<?php

return [
    'announce' => [
        'external_not_allowed' => 'Only VU SR bodies\' meetings are announced in the calendar.',
        'explainer' => 'The calendar event is the public announcement of a meeting. While it stays a draft the meeting remains internal; publishing it makes the agenda and the linked documents visible to everyone.',
        'create_hint' => 'Creates a draft carrying the meeting date and tenant. You still have to publish it.',
        'link_hint' => 'Pick an event already entered within a week of the meeting.',
        'no_nearby_events' => 'No unlinked calendar events near the meeting date.',
        'draft_hint' => 'The calendar event is still a draft — the meeting is not public.',
        'published_hint' => 'The meeting is announced in the calendar and publicly visible.',
        'spotlight_title' => 'A meeting can be announced in the calendar',
        'spotlight_description' => 'Link the meeting to a calendar event — the agenda, times and documents then show on the event page instead of being entered separately.',
        'form_alert_title' => 'This event announces a meeting',
        'form_alert_draft' => 'While the event stays a draft the meeting\'s agenda and documents are not public. Publishing it puts them on this event\'s page.',
        'form_alert_published' => 'The meeting\'s agenda and its linked documents show on this event\'s page.',
        'timing_locked' => 'The meeting owns the timing — change it on the meeting and the event follows.',
        'form_alert_trashed' => 'The meeting has been deleted',
        'review_intro' => 'You will be able to add this meeting to the public calendar too.',
        'review_checkbox_label' => 'Also announce in the calendar',
        'review_checkbox_hint' => 'Creates a draft calendar event carrying the meeting date and tenant. You still have to publish it before the agenda becomes public.',
    ],
    'documents' => [
        'empty_title' => 'No documents yet',
        'empty_description' => 'Link nutarimai or protokolai to this meeting — they will also show on the public meeting and event pages.',
        'picker_explainer' => 'Documents of this meeting\'s institutions, nearest to the meeting date first.',
        'none_available' => 'No unlinked documents for this institution.',
    ],
];
