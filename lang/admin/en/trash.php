<?php

return [
    'show_deleted' => 'Show deleted',
    'deleted_only' => 'Deleted only',
    'active_records' => 'Active',
    'deleted_records' => 'Deleted',
    'showing_deleted_only' => 'Only deleted records are being shown.',
    'showing_deleted_only_description' => 'These records are deleted. You can restore them or delete them permanently.',
    'exit_trash_view' => 'Leave trash view',
    'no_deleted_records' => 'No deleted records',
    'spotlight_title' => 'Trash view is now available',
    'spotlight_description' => 'Use this toggle to review deleted records, restore them, or permanently delete them when needed.',

    'restore' => 'Restore',
    'restore_conflict' => 'The record could not be restored — another record took its value while it was deleted.',
    'restored' => 'Record restored successfully!',

    'cancel' => 'Cancel',
    'permanently_delete' => 'Delete permanently',
    'permanently_deleted' => 'Record permanently deleted.',
    'permanently_delete_title' => 'Delete permanently?',
    'permanently_delete_description' => 'This will permanently delete the record and cannot be undone.',
    'must_be_deleted_first' => 'Only records that are already deleted can be permanently deleted.',

    'blocked' => [
        'generic' => 'This record cannot be deleted permanently — it is referenced by: :blockers. That data has to be preserved, so the record stays deleted.',
        'has_related_records' => 'This record cannot be deleted permanently because other records reference it.',
        'duty_has_membership_history' => 'This duty cannot be deleted permanently — :count membership records reference it and must be preserved. The duty stays deleted.',
    ],

    // Referenced-record labels used by trash.blocked.generic. Models that already have
    // an entities.*.model pluralisation reuse that instead of appearing here.
    'blockers' => [
        'check_ins' => '{1} activity check-in|[2,*] activity check-ins',
        'registrations' => '{1} registration|[2,*] registrations',
        'comments' => '{1} comment|[2,*] comments',
        'training_participation' => '{1} training participant|[2,*] training participants',
        'membership_history' => '{1} membership record|[2,*] membership records',
        'type_assignments' => '{1} type assignment|[2,*] type assignments',
        'organised_trainings' => '{1} organised training|[2,*] organised trainings',
        'primary_institution_of_tenant' => '{1} unit it is the primary institution for|[2,*] units it is the primary institution for',
        'reported_problems' => '{1} reported problem|[2,*] reported problems',
    ],

    // One extra sentence per model in the delete / permanent-delete dialogs, resolved
    // by model name. Models without an entry simply get the generic wording.
    'notes' => [
        'duties' => [
            'delete' => "Members' service history stays intact and the duty can be restored next term.",
        ],
        'meetings' => [
            'delete' => 'The agenda, votes and decisions are kept and come back when the meeting is restored.',
            'force_delete' => 'The agenda, votes and decisions are destroyed along with it.',
        ],
        'tags' => [
            'delete' => 'Articles keep the tag and regain it when it is restored.',
            'force_delete' => 'The tag is removed from every article.',
        ],
        'users' => [
            'delete' => 'Their duties and history are preserved — they simply stop appearing in lists.',
        ],
        'institutions' => [
            'delete' => "The institution's duties, meetings and contacts are preserved.",
        ],
        'forms' => [
            'delete' => 'Submitted registrations are kept.',
        ],
        'news' => [
            'delete' => 'It stops being public immediately, and the language pairing is released so the other language keeps working.',
            'force_delete' => 'The URL becomes free to reuse.',
        ],
        'pages' => [
            'delete' => 'It stops being public immediately, and the language pairing is released so the other language keeps working.',
            'force_delete' => 'The URL becomes free to reuse.',
        ],
        'navigation' => [
            'delete' => 'Its sub-items are deleted with it and restored with it.',
        ],
    ],

    'type_to_confirm' => 'Type this text to confirm:',
    'confirmation_label' => 'Confirmation text',
    'confirmation_placeholder' => 'Type the required text',
];
