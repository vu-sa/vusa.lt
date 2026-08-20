<?php

return [
    'timeline' => [
        'title' => 'Duty periods',
        'description' => 'Review and manage duty periods in one place.',
        'open' => 'Manage periods',
        'row_count' => ':count rows',
        'show_ended' => 'Show ended',
        'collapse_all' => 'Collapse all',
        'expand_all' => 'Expand all',
        'truncated' => 'Only the first :max rows are shown. Narrow this down to specific duties.',
        'blocked_summary' => '{1} :count row was skipped|[2,*] :count rows were skipped',
        'select_group' => 'Select every row in this group',

        'empty' => [
            'title' => 'No periods',
            'description' => 'No duty periods were found for this view.',
        ],

        'dock' => [
            'selection' => 'Selected',
            'suggestions' => 'Suggested fixes',
            'multi_hint' => ':editable of :total selected rows can be changed.',
            'save' => 'Changes',
        ],

        'zoom' => [
            'label' => 'Zoom',
            'in' => 'Zoom in',
            'out' => 'Zoom out',
        ],

        'legend' => [
            'title' => 'Legend',
            'active' => 'Currently held',
            'former' => 'Ended',
            'derived' => 'Ex officio (current)',
            'staged' => 'Unsaved change',
            'cross_tenant' => 'Representing another unit',
        ],

        'filters' => [
            'cadence' => 'Cadence',
            'tenant' => 'Unit',
            'clear' => 'Clear the filter',
            'no_cadence' => 'No cadence',
            'no_tenant' => 'No unit',
            'empty_title' => 'Nothing matches the filter',
            'empty_description' => 'Clear the cadence or unit filter to see more rows.',
        ],

        'extras' => [
            'title' => 'Extra details',
            'email' => 'Email',
            'study_program' => 'Study programme',
            'description' => 'Description',
            'photo' => 'Photo',
            'photo_set' => 'Has its own photo',
            'original_duty_name' => 'Duty name',
            'original_duty_name_set' => 'Shows the original duty name',
        ],

        'inspector' => [
            'empty' => 'Select a bar to see its exact dates.',
            'start_date' => 'Start',
            'end_date' => 'End',
            'open_ended_toggle' => 'Keep open-ended',
            'ex_officio' => 'Ex officio',
            'ex_officio_managed' => 'These dates follow the “:duty” duty and can only be changed there.',
            'select_source' => 'Select the source row',
            'aligned' => 'Matches the cadence start.',
            'off_by' => ':days days from the cadence start.',
            'not_editable' => 'You cannot edit this row.',
        ],

        'actions' => [
            'apply_dates' => 'Apply dates (:count)',
            'merge' => 'Merge',
            'merge_title' => 'Merge these periods?',
            'merge_description' => ':count of :holder’s periods on “:duty” become one: :start → :end. The other rows are deleted.',
            'merge_extras_warning' => 'More than one row carries extra details. Only the earliest row’s are kept — the others’ email, study programme or description are lost.',
            'merge_confirm' => 'Merge',
            'merge_hint' => 'Only one person’s periods on one duty can be merged.',
            'merge_invalid' => 'Only one person’s periods on one duty can be merged.',
            'merge_done' => ':count periods merged.',
            'align' => 'Align',
            'close' => 'End',
            'close_end_date' => 'End date',
            'close_yesterday' => "Yesterday's date (:date)",
            'close_hint' => 'This is the date the rest of the system uses when ending a duty.',
            'close_run' => 'Preview changes',
            'remove' => 'Remove',
            'remove_title' => 'Remove this duty period?',
            'remove_description' => ':holder will no longer hold “:duty” for this period, and any access it granted goes with it. This cannot be undone.',
            'remove_confirm' => 'Remove',
            'remove_cancel' => 'Cancel',
        ],

        'drag' => [
            'hint' => 'Drag a bar to move it whole months, keeping the day. Drag an edge to change one date, Alt for exact days, Esc to cancel.',
        ],

        'staging' => [
            'dirty_count' => '{1} :count unsaved change|[2,*] :count unsaved changes',
            'clean' => 'Everything is saved.',
            'preview' => 'Preview',
            'discard' => 'Discard',
            'save' => 'Save',
            'saving' => 'Saving…',
            'sync_pending' => 'Syncing ex officio rows',
        ],

        'diff' => [
            'title' => 'Change preview',
            'description' => 'This is how the rows will look once saved.',
            'changed' => ':count changing',
            'blocked' => ':count skipped',
            'unchanged' => ':count unchanged',
            'derived' => ':count ex officio rows will follow',
            'no_changes' => 'Nothing would change.',
            'self_affecting' => 'One of your own duties is among the rows being changed. Saving may ask you to confirm an access change.',
            'diagnostics_delta' => 'Issues: :before → :after',
            'confirm' => 'Save',
            'cancel' => 'Back',
        ],

        'blocked' => [
            'derived' => 'Ex officio row — its dates follow the source.',
            'inverted' => 'The end would fall before the start.',
        ],

        'diagnostics' => [
            'empty' => 'No issues found.',
            'apply_selected' => 'Apply selected (:count)',
            'codes' => [
                'inverted' => 'Ends before it starts',
                'overlap' => 'Overlapping periods',
                'boundary_shared' => 'One period ends on the day the next begins',
                'open_ended_stale' => 'Open-ended although the cadence has ended',
                'ex_officio_drift' => 'Ex officio dates differ from their source',
                'off_cadence' => 'Date does not match the cadence boundary',
                'spans_cadences' => 'Spans more than one cadence',
                'understaffed' => 'Fewer holders than places to occupy',
                'orphan_derived_suspect' => 'Suspicious ex officio row with no source',
            ],
            'detail' => [
                'end_move' => 'end :from → :to',
                'clear_end' => 'the end would be cleared',
                'close_at' => 'end on :date',
                'drift_start' => 'start is :days days off',
                'drift_end' => 'end is :days days off',
                'spans' => ':count cadences · would become :start → :end',
                'understaffed' => ':active of :places places filled',
                'ex_officio_drift' => 'Fix this by moving the source row.',
            ],
            'orphan_note' => 'These rows grant real permissions and their link to a source is already gone, so nothing here touches them automatically. Run “duties:audit-ex-officio”.',
        ],

        'page' => [
            'title' => 'Duty periods',
            'description' => 'Review and repair every duty period across one institution.',
            'pick_institution' => 'Pick an institution',
            'change_institution' => 'Change institution',
            'no_scope' => 'Pick an institution to see its periods.',
        ],

        'spotlight' => [
            'title' => 'Periods can be managed in one place',
            'description' => 'Instead of editing each member’s period on its own page, open the timeline and fix them all at once.',
        ],
    ],
];
