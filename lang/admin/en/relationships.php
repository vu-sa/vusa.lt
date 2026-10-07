<?php

return [
    // Section headings on an institution's record, by who sees whom.
    'group_outgoing' => 'Oversees',
    'group_incoming' => 'Overseen by',
    'group_mutual' => 'Parallel',
    'group_other' => 'Related',

    // Authorization
    'authorized' => 'Authorized',
    'not_authorized' => 'Not authorized',

    // Tooltip
    'tooltip_via' => 'Via relationship with',
    'tooltip_authorized' => 'Full data access',
    'tooltip_not_authorized' => 'Meeting view only (no agenda)',

    // Graph visualisation (legend, tooltips)
    'graph' => [
        'legend_title' => 'Relationship types',
        'scope_legend_title' => 'Relationship scope',
        'directional' => 'Directional',
        'bidirectional' => 'Bidirectional',
        'type_direct' => 'Direct',
        'type_type_based' => 'By type',
        'type_cross_tenant' => 'Cross-unit',
        'direction_outgoing' => 'Outgoing',
        'direction_incoming' => 'Incoming',
        'direction_mutual' => 'Mutual',
        'scope_within_tenant' => 'Within the same unit',
        'scope_cross_tenant' => 'Across units',
        'and_more' => '+:count more',
    ],
];
