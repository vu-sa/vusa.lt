<?php

return [
    'filters' => [
        'all' => 'All',
        'completed' => 'Completed',
        'incomplete' => 'Incomplete',
        'label' => 'Label',
        'select_filter' => 'Select filter',
    ],
    'create_new' => 'Create New',
    'due' => 'Due',
    'auto_completing' => 'Auto-completing',
    'instructions' => 'Instructions',
    'available_actions' => 'Available actions',
    'assigned_to' => 'Assigned to',
    'stats' => [
        'pending' => 'Pending tasks',
        'pending_caption' => 'Not yet completed',
        'auto_completing' => 'Auto-completing',
        'auto_completing_caption' => 'Closed by the system',
        'overdue_caption' => 'Past their due date',
        'completed_caption' => 'Done',
    ],
    'periodicity_gap' => [
        'name' => 'Report institution activity for :institution',
        'description' => 'The institution activity reporting period is approaching its threshold. Register a new meeting or report the activity.',
        'completed_meeting_created' => 'Meeting registered',
        'completed_checkin_created' => 'Activity report created',
        'schedule_meeting' => 'Register meeting',
        'report_no_meeting' => 'Report activity',
        'action_schedule_meeting' => 'Register meeting',
        'action_report_no_meeting' => 'Report activity',
    ],
    'agenda_creation' => [
        'meeting_context' => 'Meeting: :institution (:date).',
        'assignee_context' => 'You and :count other(s) have this task.',
        'first_item_created' => 'First agenda item created',
    ],
    'agenda_completion' => [
        'meeting_context' => 'Meeting: :institution (:date).',
        'assignee_context' => 'You and :count other(s) have this task.',
        'all_items_completed' => 'All agenda items completed',
    ],
    'delete_automatic' => 'Delete (as super admin)',
    'delete_confirm_title' => 'Delete this task?',
    'delete_confirm_description' => '":name" will be removed permanently. This cannot be undone.',
    'orphaned' => 'Subject deleted',
    'orphaned_description' => 'The record this task was filed against no longer exists, so the task can never complete on its own.',
    'agenda' => [
        'action_add_items' => 'Add agenda item',
        'action_view_agenda' => 'View agenda',
    ],
];
