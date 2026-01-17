<?php

return [
    // Categories
    'categories' => [
        'comment' => 'Comments',
        'task' => 'Tasks',
        'reservation' => 'Reservations',
        'meeting' => 'Meetings',
        'registration' => 'Registrations',
        'user' => 'User',
        'duty' => 'Duties',
        'system' => 'System',
    ],

    // Channels
    'channels' => [
        'in_app' => 'In-App',
        'in_app_description' => 'Receive notifications directly in the app',
        'push' => 'Push Notifications',
        'push_description' => 'Receive notifications to your browser/device',
        'email_digest' => 'Email Digest',
        'email_digest_description' => 'Receive notifications via periodic email digest',
    ],

    // Digest
    'digest_subject' => '{1} You have :count new notification|[2,*] You have :count new notifications',
    'digest_greeting' => 'Hello, :name!',
    'digest_intro' => 'Here is your notification digest (:count notifications):',
    'digest_intro_short' => 'Here\'s your notification summary:',
    'digest_footer' => 'You can change notification settings in your profile.',
    'digest_count_label' => '{1} notification|[2,*] notifications',
    'digest_total' => 'total',
    'digest_more_items' => '{1} :count more item|[2,*] :count more items',
    'digest_view_all' => 'View all notifications',

    // Comment notifications
    'comment_posted_title' => 'New comment: :name',
    'comment_posted_body' => ':commenter left a comment: :comment',
    'commented_on' => 'commented on',
    'changed_status_on' => 'changed status on',
    'left_comment_on' => 'left a comment on',

    // Assignment notifications
    'assigned_to_resource_title' => 'Assigned to: :resource',
    'assigned_to_resource_body' => ':assigner assigned you to :resource',
    'assigned_to_resource_greeting' => 'You have been assigned to a resource',
    'assigned_you_to' => 'assigned you to',

    // Task notifications
    'task_assigned_title' => 'New Task',
    'task_assigned_body' => 'You have been assigned a new task: :task',
    'task_assigned_body_with_assigner' => ':assigner assigned you a task: :task',
    'task_reminder_title' => 'Task Reminder (:days days)',
    'task_reminder_body' => ':days days left until task ":task" is due',
    'task_completed_title' => 'Task Completed',
    'task_completed_body' => ':user completed task ":task"',
    'task_auto_completed_title' => 'Task Auto-Completed',
    'task_auto_completed_body' => 'Task ":task" was automatically completed: :reason',
    'task_overdue_title' => '{1} You have :count overdue task|[2,*] You have :count overdue tasks',
    'task_overdue_body_single' => 'Task ":task" is overdue',
    'task_overdue_body_multiple' => 'You have :count overdue tasks: :tasks and more',
    'overdue_tasks' => 'Overdue Tasks',

    // Registration notifications
    'member_registered_title' => 'New Member Registration',
    'member_registered_body' => ':name registered for :institution',
    'student_rep_registered_title' => 'New Representative Registration',
    'student_rep_registered_body' => ':name registered as representative: :institution',

    // Reservation notifications
    'reservation_status_changed_title' => 'Reservation status: :status',
    'reservation_status_changed_body' => 'Reservation ":reservation" (:resource) changed to :status',
    'reservation_status_changed_body_with_user' => ':user changed reservation ":reservation" (:resource) status to :status',
    'reservation_task_hint_pickup' => '📋 Pick up :resource by :date.',
    'reservation_task_hint_return' => '📋 Return :resource by :date.',

    // Approval notifications
    'approval_requested_title' => 'Approval Required',
    'approval_requested_body' => 'Your approval is needed for: :item',
    'approval_approved_title' => 'Approved',
    'approval_approved_body' => ':user approved :item',
    'approval_rejected_title' => 'Rejected',
    'approval_rejected_body' => ':user rejected :item',
    'approval_cancelled_title' => 'Cancelled',
    'approval_cancelled_body' => ':user cancelled :item',
    'approval_escalation_title' => 'Approval Overdue',
    'approval_escalation_body' => 'Approval for :item is overdue and requires your attention',

    // Meeting notifications
    'meeting_reminder_title' => 'Upcoming Meeting',
    'meeting_reminder_soon_title' => 'Meeting Soon!',
    'meeting_reminder_body' => 'Meeting ":meeting" starts in :hours hours',
    'meeting_reminder_body_one_hour' => 'Meeting ":meeting" starts in 1 hour',
    'meeting_created_title' => 'New Meeting Created',
    'meeting_created_body' => 'A new meeting for :institution has been created for :date',
    'meeting_agenda_completed_title' => 'Meeting Agenda Completed',
    'meeting_agenda_completed_body' => 'All :count agenda items for :institution meeting have been completed',

    // Duty notifications
    'duty_expiring_title' => 'Duty expires in :days days',
    'duty_expiring_body' => 'Your duty ":duty" ends on :date. Remember to transfer your experience!',

    // System notifications
    'welcome_title' => 'Welcome to VU SA Mano! 🎉',
    'welcome_body' => 'Great job, :name! You\'ve completed your first intro to the platform. Enjoy exploring!',
    'test_notification_title' => 'Test Notification',
    'test_notification_body' => 'This is a test notification! Push notifications are working.',

    // Actions
    'action_view_comment' => 'View Comment',
    'action_view_resource' => 'View Resource',
    'action_view_tasks' => 'View Tasks',
    'action_view_registration' => 'View Registration',
    'action_view_reservation' => 'View Reservation',
    'action_view_meeting' => 'View Meeting',
    'action_view_duty' => 'View Duty',
    'action_explore_dashboard' => 'Explore Dashboard',
    'action_view' => 'View',
    'action_review' => 'Review',

    // Preferences UI
    'preferences' => [
        'title' => 'Notification Settings',
        'description' => 'Choose how and when you want to receive notifications.',
        'mute_all' => 'Temporary Mute',
        'mute_all_description' => 'Temporarily disable all notifications',
        'mute' => 'Mute...',
        'muted_until' => 'Muted until :date',
        'unmute' => 'Unmute',
        'digest_frequency' => 'Digest Frequency',
        'digest_frequency_description' => 'How often to send email digest',
        'category_settings' => 'Notification Categories',
        'category_settings_description' => 'Choose which notification types you want to receive on each channel.',
        'reminder_settings' => 'Reminder Settings',
        'task_reminder_days' => 'Task Reminder Days',
        'task_reminder_days_description' => 'How many days before to remind about upcoming task deadlines',
        'meeting_reminder_hours' => 'Meeting Reminder Hours',
        'meeting_reminder_hours_description' => 'How many hours before to remind about upcoming meetings',
    ],

    // Legacy compatibility
    'preferences_title' => 'Notification Settings',
    'preferences_description' => 'Choose how you want to receive notifications',
    'digest_frequency' => 'Email Digest Frequency',
    'digest_frequency_1' => 'Every 1 hour',
    'digest_frequency_4' => 'Every 4 hours',
    'digest_frequency_12' => 'Every 12 hours',
    'digest_frequency_24' => 'Once per day',
    'mute_all' => 'Mute All Notifications',
    'mute_for_1h' => 'Mute for 1 hour',
    'mute_for_4h' => 'Mute for 4 hours',
    'mute_until_tomorrow' => 'Mute until tomorrow',
    'unmute' => 'Unmute Notifications',
    'muted_until' => 'Notifications muted until :time',
    'mute_thread' => 'Mute this thread',
    'unmute_thread' => 'Unmute this thread',

    // Reminder settings
    'reminder_settings' => 'Reminder Settings',
    'task_reminder_days' => 'Task Reminder Days',
    'task_reminder_days_description' => 'How many days before to remind about upcoming tasks',
    'meeting_reminder_hours' => 'Meeting Reminder Hours',
    'meeting_reminder_hours_description' => 'How many hours before to remind about upcoming meetings',

    // Notification catalog
    'catalog_title' => 'Notification Catalog',
    'catalog_description' => 'All notifications sent by the system',
];
