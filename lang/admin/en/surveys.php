<?php

return [
    'title' => 'Surveys',
    'question_bank' => 'Question bank',
    'default_group' => 'Questions',

    'status' => [
        'draft' => 'Draft',
        'pending_approval' => 'Pending approval',
        'approved' => 'Approved',
        'rejected' => 'Rejected',
        'active' => 'Running',
        'closed' => 'Closed',
    ],

    'question_type' => [
        'short_text' => 'Short text',
        'long_text' => 'Long text',
        'list' => 'Single choice',
        'multiple_choice' => 'Multiple choice',
        'five_point' => 'Rating 1–5',
    ],

    'fields' => [
        'status' => 'Status',
        'name' => 'Title',
        'description' => 'Description',
        'welcome_text' => 'Welcome text',
        'starts_at' => 'Starts',
        'ends_at' => 'Ends',
        'is_anonymous' => 'Anonymous survey',
        'tenant' => 'Unit',
        'group_name' => 'Question group',
        'title' => 'Question code',
        'type' => 'Type',
        'question' => 'Question',
        'help' => 'Help text',
        'is_required' => 'Required',
        'options' => 'Answer options',
        'option_code' => 'Code',
        'option_label' => 'Label',
        'is_active' => 'Active',
    ],

    'global_template' => 'Shared',

    'helpers' => [
        'anonymous' => 'LimeSurvey will not store a link between a response and the respondent.',
        'question_code' => 'Short code used as the LimeSurvey result column (e.g. Q01).',
        'no_questions_yet' => 'No questions yet. Add one from the bank or write a new one.',
        'locked' => 'This survey is published, so its questions can no longer be changed.',
        'global_template' => 'Leave empty to make the template available to every unit.',
    ],

    'sections' => [
        'questions' => 'Questions',
        'approval' => 'Approval',
        'limesurvey' => 'LimeSurvey',
    ],

    'actions' => [
        'create' => 'New survey',
        'add_question' => 'Add question',
        'add_from_template' => 'Add from bank',
        'save_questions' => 'Save questions',
        'request_approval' => 'Submit for approval',
        'resync' => 'Refresh from LimeSurvey',
        'retry_publish' => 'Retry publishing',
        'open_survey' => 'Open survey',
        'add_option' => 'Add option',
    ],

    'limesurvey' => [
        'not_configured' => 'The LimeSurvey integration is not configured.',
        'not_published' => 'This survey has not been published to LimeSurvey yet.',
        'survey_id' => 'LimeSurvey ID',
        'public_url' => 'Public link',
        'sync_status' => 'Sync status',
        'last_synced' => 'Last refreshed',
        'completed' => 'Completed',
        'incomplete' => 'Started',
        'full' => 'Total',
        'locked_notice' => 'This survey is published — LimeSurvey does not allow structural changes.',
    ],

    'validation' => [
        'duplicate_titles' => 'Question codes must be unique.',
        'options_required' => 'This question type needs at least one answer option.',
    ],

    'flash' => [
        'created' => 'Survey created.',
        'updated' => 'Survey updated.',
        'deleted' => 'Survey deleted.',
        'questions_saved' => 'Questions saved.',
        'approval_requested' => 'Survey submitted for approval.',
        'no_questions' => 'The survey has no questions.',
        'no_flow' => 'No survey approval flow found. Run ApprovalFlowSeeder.',
        'not_approved' => 'The survey has not been approved yet.',
        'publish_queued' => 'Publishing has been queued.',
        'stats_queued' => 'Statistics refresh has been queued.',
        'template_created' => 'Question template created.',
        'template_updated' => 'Question template updated.',
        'template_deleted' => 'Question template deleted.',
    ],
];
