<?php

return [
    'duty' => [
        'model' => '{1} duty|[2,*] duties',
    ],
    'document' => [
        'model' => '{1} document|[2,*] documents',
    ],
    'user' => [
        'model' => '{1} member|[2,*] members',
    ],
    'news' => [
        'model' => '{1} news item|[2,*] news items',
    ],
    'page' => [
        'model' => '{1} page|[2,*] pages',
    ],
    'banner' => [
        'model' => '{1} banner|[2,*] banners',
    ],
    'category' => [
        'model' => '{1} category|[2,*] categories',
    ],
    'tag' => [
        'model' => '{1} tag|[2,*] tags',
    ],
    'type' => [
        'model' => '{1} content type|[2,*] content types',
    ],
    'relationship' => [
        'model' => '{1} relationship|[2,*] relationships',
    ],
    'calendar' => [
        'model' => '{1} event|[2,*] events',
    ],
    'form' => [
        'model' => '{1} form|[2,*] forms',
    ],
    'role' => [
        'model' => '{1} role|[2,*] roles',
    ],
    'permission' => [
        'model' => '{1} permission|[2,*] permissions',
    ],
    'studyProgram' => [
        'model' => '{1} study programme|[2,*] study programmes',
    ],
    'studySet' => [
        'model' => '{1} individual study set|[2,*] individual study sets',
    ],
    'institution' => [
        'model' => '{1} institution|[2,*] institutions',
    ],
    'meeting' => [
        'model' => '{1} meeting|[2,*] meetings',
    ],
    'tenant' => [
        'model' => '{1} unit|[2,*] units',
    ],
    'reservation' => [
        'model' => '{1} reservation|[2,*] reservations',
        'managers' => '{1} reservation manager|[2,*] reservation managers',
        'start_time' => 'start time',
        'end_time' => 'end time',
        'resources' => 'reserved resources',
        'is_reservable' => 'is reservable?',
        'period' => 'reservation period',
    ],
    'resource' => [
        'model' => '{1} resource|[2,*] resources',
    ],
    'resource_category' => [
        'model' => '{1} resource category|[2,*] resource categories',
    ],
    'reservation_resource' => [
        'model' => '{1} reservation resource|[2,*] reservation resources',
    ],
    'meta' => [
        'model_list' => ':model list',
        'help' => 'How :model work?',
    ],
    'problem' => [
        'model' => '{1} problem|[2,*] problems',
        'title' => 'problem title',
        'description' => 'problem description',
        'solution' => 'solution',
        'steps_taken' => 'Steps taken',
        'occurred_at' => 'occurred date',
        'resolved_at' => 'resolved date',
        'status' => 'status',
        'responsible_user' => 'responsible person',
        'categories' => 'categories',
        'status_options' => [
            'open' => 'Open',
            'in_progress' => 'In progress',
            'resolved' => 'Resolved',
        ],
    ],

    'contentPart' => [
        'content_summary' => 'content',
        'type' => 'block type',
        'options' => 'settings',
    ],

    // Fallback field labels shared across the activity log for any model
    // without a more specific entry above -- see App\Services\ActivityChangeFormatter.
    'common' => [
        'name' => 'name',
        'title' => 'title',
        'short_name' => 'short name',
        'description' => 'description',
        'order' => 'order',
        'is_active' => 'active?',
        'start_time' => 'start time',
        'end_time' => 'end time',
        'address' => 'address',
        'email' => 'email',
        'phone' => 'phone',
        'url' => 'URL',
        'image_url' => 'image URL',
        'link_url' => 'link URL',
        'lang' => 'language',
        'status' => 'status',
        'note' => 'note',
        'notes_html' => 'notes',
        'permalink' => 'permalink',
        'publish_time' => 'publish time',
        'main_image' => 'main image',
        'location' => 'location',
        'organizer' => 'organizer',
        'video_url' => 'video URL',
        'facebook_url' => 'Facebook URL',
        'max_participants' => 'participant limit',
        'tenant' => 'unit',
        'institution' => 'institution',
        'category' => 'category',
        'meeting' => 'meeting',
        'agenda_item' => 'agenda item',
        // Relation names, for relation_updated activities (see
        // App\Support\AuditedRelations / LogsRelationshipChanges).
        'users' => 'members',
        'types' => 'types',
        'institutions' => 'institutions',
        'resources' => 'resources',
    ],
];
