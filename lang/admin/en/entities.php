<?php

/*
 * Entity names used across the admin UI (table empty states, breadcrumbs, flash messages).
 *
 * 'gender' annotates the Lithuanian noun in lang/admin/lt/entities.php ('f' / 'm'). English
 * participles do not agree with gender, so both variants in messages.php read the same — the
 * key only stays here so the lt/en files hold the same keys (TranslationIntegrityTest).
 */

return [
    'duty' => [
        'model' => '{1} duty|[2,*] duties',
        'gender' => 'f',
    ],
    'dutiable' => [
        'model' => '{1} duty period|[2,*] duty periods',
        'gender' => 'm',
    ],
    'document' => [
        'model' => '{1} document|[2,*] documents',
        'gender' => 'm',
        'title' => 'title',
        'sharepoint_id' => 'SharePoint ID',
        'eTag' => 'eTag',
        'document_date' => 'document date',
        'language' => 'language',
        'content_type' => 'content type',
        'institution_id' => 'institution',
        'public_url' => 'public URL',
        'public_url_created_at' => 'public URL created at',
        'thumbnail_url' => 'thumbnail URL',
        'is_active' => 'active?',
        'sharepoint_site_id' => 'SharePoint site ID',
        'sharepoint_list_id' => 'SharePoint list ID',
    ],
    'user' => [
        'model' => '{1} member|[2,*] members',
        'gender' => 'm',
    ],
    'news' => [
        'model' => '{1} news item|[2,*] news items',
        'gender' => 'f',
    ],
    'page' => [
        'model' => '{1} page|[2,*] pages',
        'gender' => 'm',
    ],
    'banner' => [
        'model' => '{1} banner|[2,*] banners',
        'gender' => 'm',
    ],
    'category' => [
        'model' => '{1} category|[2,*] categories',
        'gender' => 'f',
    ],
    'tag' => [
        'model' => '{1} tag|[2,*] tags',
        'gender' => 'f',
    ],
    'type' => [
        'model' => '{1} content type|[2,*] content types',
        'gender' => 'm',
    ],
    'relationship' => [
        'model' => '{1} relationship|[2,*] relationships',
        'gender' => 'm',
    ],
    'relationshipType' => [
        'model' => '{1} relationship type|[2,*] relationship types',
        'gender' => 'm',
    ],
    'calendar' => [
        'model' => '{1} event|[2,*] events',
        'gender' => 'm',
    ],
    'form' => [
        'model' => '{1} form|[2,*] forms',
        'gender' => 'f',
    ],
    'role' => [
        'model' => '{1} role|[2,*] roles',
        'gender' => 'f',
    ],
    'permission' => [
        'model' => '{1} permission|[2,*] permissions',
        'gender' => 'f',
    ],
    'studyProgram' => [
        'model' => '{1} study programme|[2,*] study programmes',
        'gender' => 'f',
    ],
    'studySet' => [
        'model' => '{1} individual study set|[2,*] individual study sets',
        'gender' => 'm',
    ],
    'institution' => [
        'model' => '{1} institution|[2,*] institutions',
        'gender' => 'f',
        'name' => 'name',
        'description' => 'description',
        'is_active' => 'active?',
        'is_default' => 'default?',
        'is_public' => 'public?',
        'is_visible' => 'visible?',
    ],
    'meeting' => [
        'model' => '{1} meeting|[2,*] meetings',
        'gender' => 'm',
    ],
    'agendaItem' => [
        'model' => '{1} agenda item|[2,*] agenda items',
        'gender' => 'm',
    ],
    'vote' => [
        'model' => '{1} vote|[2,*] votes',
        'gender' => 'm',
    ],
    'tenant' => [
        'model' => '{1} unit|[2,*] units',
        'gender' => 'm',
    ],
    'reservation' => [
        'model' => '{1} reservation|[2,*] reservations',
        'gender' => 'f',
        'managers' => '{1} reservation manager|[2,*] reservation managers',
        'start_time' => 'start time',
        'end_time' => 'end time',
        'resources' => 'reserved resources',
        'is_reservable' => 'is reservable?',
        'period' => 'reservation period',
    ],
    'resource' => [
        'model' => '{1} resource|[2,*] resources',
        'gender' => 'm',
    ],
    'resourceCategory' => [
        'model' => '{1} resource category|[2,*] resource categories',
        'gender' => 'f',
    ],
    'reservationResource' => [
        'model' => '{1} reservation resource|[2,*] reservation resources',
        'gender' => 'm',
    ],
    'comment' => [
        'model' => '{1} comment|[2,*] comments',
        'gender' => 'm',
    ],
    'task' => [
        'model' => '{1} task|[2,*] tasks',
        'gender' => 'f',
    ],
    'quickLink' => [
        'model' => '{1} quick link|[2,*] quick links',
        'gender' => 'f',
    ],
    'navigation' => [
        'model' => '{1} navigation item|[2,*] navigation items',
        'gender' => 'm',
    ],
    'file' => [
        'model' => '{1} file|[2,*] files',
        'gender' => 'm',
    ],
    'folder' => [
        'model' => '{1} folder|[2,*] folders',
        'gender' => 'm',
    ],
    'meta' => [
        'model_list' => ':model list',
        'help' => 'How :model work?',
    ],
    'problem' => [
        'model' => '{1} problem|[2,*] problems',
        'gender' => 'f',
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
