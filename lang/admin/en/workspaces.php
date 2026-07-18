<?php

return [
    'title' => 'Workspaces',
    'my_workspaces' => 'My workspaces',
    'create' => 'Create workspace',
    'name' => 'Name',
    'description' => 'Description',
    'institution' => 'Institution',
    'institution_hint' => 'Attaching an institution automatically grants its current representatives access to this workspace.',
    'no_institution' => 'No institution',
    'no_workspaces' => 'You have no workspaces yet. Create your first one to prepare for meetings!',
    'documents_count' => '{0} No documents|{1} :count document|[2,*] :count documents',
    'delete' => 'Delete workspace',
    'delete_confirm' => 'Are you sure you want to delete this workspace? Documents and discussions will become unavailable.',

    'tabs' => [
        'documents' => 'Documents',
        'discussion' => 'Discussion',
        'links' => 'Links',
        'members' => 'Members',
    ],

    'documents' => [
        'create' => 'New document',
        'title_placeholder' => 'Document title, e.g. "Preparation for the October meeting"',
        'empty' => 'No documents yet. Create the first one for shared preparation!',
        'rename' => 'Rename',
        'archive' => 'Archive',
        'archive_confirm' => 'Archive this document?',
        'last_edited' => 'Last edited by',
        'editing_now' => 'editing now',
    ],

    'links' => [
        'add' => 'Add link',
        'empty' => 'No linked records. Link the meetings or problems you are preparing for.',
        'search_placeholder' => 'Search by title…',
        'type_meeting' => 'Meeting',
        'type_agendaItem' => 'Agenda item',
        'type_problem' => 'Problem',
        'type_institution' => 'Institution',
        'added_by' => 'Added by',
    ],

    'members' => [
        'invite' => 'Invite member',
        'search_placeholder' => 'Search users by name…',
        'role' => 'Role',
        'remove' => 'Remove',
        'remove_confirm' => 'Remove this member from the workspace?',
        'via_institution' => 'Access via institution',
        'empty' => 'This workspace has no members yet.',
    ],

    'member_added' => 'Member invited to the workspace.',
    'member_updated' => 'Member role updated.',
    'member_removed' => 'Member removed from the workspace.',
    'last_admin' => 'The last workspace administrator cannot be removed.',
    'link_added' => 'Record linked to the workspace.',
    'link_removed' => 'Record link removed.',
    'link_not_found' => 'Record not found.',
    'institution_not_allowed' => 'You can only attach an institution where you currently hold a duty.',
    'document_created' => 'Document created.',
    'document_updated' => 'Document updated.',
    'document_archived' => 'Document archived.',
    'document_saved' => 'Document saved.',
];
