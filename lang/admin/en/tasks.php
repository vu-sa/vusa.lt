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
        'auto_completing' => 'Auto-completing',
    ],
    'periodicity_gap' => [
        'name' => 'Report on :institution activity',
        'description' => 'Institution meeting periodicity is approaching its threshold. Schedule a new meeting or report that no meeting is planned.',
        'completed_meeting_created' => 'Meeting scheduled',
        'completed_checkin_created' => 'Check-in record created',
        'schedule_meeting' => 'Schedule meeting',
        'report_no_meeting' => 'Report no meeting',
        'action_schedule_meeting' => 'Schedule meeting',
        'action_report_no_meeting' => 'Report no meeting',
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
];
