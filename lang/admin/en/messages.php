<?php

/*
 * Flash messages shown after an admin action.
 *
 * The four CRUD actions keep an 'f' and an 'm' variant because Lithuanian participles agree
 * with the entity's gender. English has no such agreement, so both variants read the same —
 * they exist only so the lt/en files hold matching keys.
 */

return [
    'created' => [
        'f' => ':Model created successfully.',
        'm' => ':Model created successfully.',
    ],
    'updated' => [
        'f' => ':Model updated successfully.',
        'm' => ':Model updated successfully.',
    ],
    'deleted' => [
        'f' => ':Model deleted successfully.',
        'm' => ':Model deleted successfully.',
    ],
    'restored' => [
        'f' => ':Model restored successfully.',
        'm' => ':Model restored successfully.',
    ],
    'users_attached_to_reservation' => 'Users attached to reservation!',

    'auth' => [
        'logout_success' => 'You have been logged out.',
        'password_changed' => 'Password changed successfully.',
        'login_cancelled' => 'Login was cancelled. Please try again if you wish to sign in.',
        'login_error' => 'An error occurred during login. Please try again.',
        'login_failed' => 'Login failed. Please try again.',
        'login_unexpected_error' => 'An unexpected error occurred. Please try again.',
        'duty_email_many_users' => 'Could not sign in with a duty email address, because it has more than one active user. Please contact an administrator.',
        'duty_email_no_user' => 'Could not sign in with a duty email address, because it has no active user. Try clearing your cookies or using a private browser window.',
        'no_account_found' => 'No account or duty was found with this email address. Please contact a VU SR student representative coordinator or administrator to get access.',
    ],

    'meeting' => [
        'created' => 'Meeting created successfully!',
        'create_failed' => 'Could not create the meeting.',
        'updated' => 'Meeting updated successfully.',
        'deleted' => 'Meeting deleted successfully!',
        'restored' => 'Meeting restored successfully.',
        'institution_attached' => 'Institution attached to the meeting.',
        'calendar_event_created' => 'Draft calendar event created. Publish it to make the meeting publicly visible.',
        'calendar_event_linked' => 'Meeting linked to the calendar event.',
        'calendar_event_unlinked' => 'Meeting unlinked from the calendar event. The event itself was kept.',
        'document_linked' => 'Document linked to the meeting.',
        'document_unlinked' => 'Document unlinked from the meeting.',
        'institution_detached' => 'Institution detached from the meeting.',
        'institution_required' => 'A meeting must keep at least one institution.',
    ],

    'agenda_item' => [
        'created_many' => 'Agenda items created successfully!',
        'reordered' => 'Agenda item order updated successfully!',
        'notes_saved' => 'Notes saved.',
    ],

    'vote' => [
        'main_changed' => 'Main vote changed successfully!',
    ],

    'comment' => [
        'invalid_type' => 'Invalid comment type.',
        'model_not_found' => 'Model not found.',
    ],

    'document' => [
        'none_to_process' => 'No documents to process.',
        'stored' => 'Documents have been successfully stored.',
        'refresh_queued' => 'Document refresh has been queued. It will be updated shortly.',
        'bulk_sync_queued' => 'Bulk sync queued for :count documents. They will be updated shortly.',
    ],

    'duty' => [
        'order_updated' => 'Duty order updated successfully!',
        'email_updated' => 'Duty email updated successfully!',
    ],

    'institution' => [
        'cannot_delete_own' => 'You cannot delete an institution you belong to!',
    ],

    'navigation' => [
        'order_updated' => 'Navigation order updated.',
    ],

    'news' => [
        'duplicated' => 'News item duplicated successfully!',
        'no_available_tenant' => 'There is no unit available for you to create news for.',
    ],

    'quick_link' => [
        'order_updated' => 'Quick link order updated successfully!',
    ],

    'relationship' => [
        'model_relation_deleted' => 'Relationship between models deleted.',
        'type_model_relation_deleted' => 'Relationship type between models deleted.',
    ],

    'role' => [
        'not_editable' => 'This role cannot be edited.',
        'not_deletable' => 'This role cannot be deleted.',
        'permissions_updated' => 'Role permissions updated.',
        'attachables_updated' => 'Role attachables updated.',
        'duties_updated' => 'Role duties updated.',
        'not_assignable_to_duty' => 'This role cannot be assigned to duties! Please try again.',
    ],

    'study_program' => [
        'merged' => 'Study programmes merged successfully.',
        'in_use' => 'Cannot delete this study programme. It is currently assigned to :count duty assignment(s).',
    ],

    'task' => [
        'automatic_not_deletable' => 'This task completes automatically and cannot be deleted.',
        'automatic_not_markable' => 'This task completes automatically and cannot be marked manually.',
        'status_updated' => 'Task status updated successfully.',
    ],

    'user' => [
        'merged' => 'Contacts merged successfully!',
        'password_created' => 'Password created successfully!',
        'password_deleted' => 'Password deleted successfully!',
    ],

    'dashboard' => [
        'settings_saved' => 'Settings saved.',
        'notification_settings_saved' => 'Notification settings saved.',
    ],

    'feedback' => [
        'thanks' => 'Thank you for your feedback!',
    ],

    'calendar' => [
        'image_deleted' => 'Image deleted!',
    ],

    'sharepoint' => [
        'not_fileable' => 'This file cannot be attached to the object.',
        'fileable_missing' => 'The related object does not exist.',
        'fileable_not_allowed' => 'The related object cannot hold files.',
        'uploaded' => 'File uploaded to SharePoint successfully!',
        'file_deleted' => 'File deleted.',
        'invalid_request' => 'Invalid request. Please report this to an administrator.',
        'deleted_locally_only' => 'The file was marked as deleted, but the SharePoint operation failed.',
    ],
];
