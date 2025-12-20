<?php

return [
    'create_new' => 'Create new relationship',
    'create' => 'Create',
    'model_type' => 'Model type',
    'select_model_type' => 'Select model type',

    // Source and target labels
    'source' => 'Relationship source',
    'source_hint' => 'will be able to see target',
    'target' => 'Relationship target',
    'target_hint' => 'will be visible to source',
    'select_source' => 'Select source',
    'select_target' => 'Select target',
    'search_model' => 'Search...',
    'no_results' => 'No results found',

    // Relationship types
    'type_based' => 'Type-based',
    'direct' => 'Direct',

    // Model connections section
    'model_connections' => 'Model connections',
    'no_connections' => 'No connections',
    'no_connections_description' => 'Add connections between models so they can see each other\'s data.',
    'create_first' => 'Create first connection',
    'edit_connection' => 'Edit connection',
    'edit_connection_description' => 'Update the connection settings.',
    'create_new_description' => 'Create a new connection between models.',
    'confirm_delete' => 'Are you sure you want to delete this connection?',

    // Access explanation
    'access_explanation_title' => 'Access explanation',
    'access_direct' => ':source members will be able to see :target meetings, information, and manage some data. :target members will not see :source data.',
    'access_type_within' => 'All :source type institutions will be able to see :target type institution data <strong>within the same tenant</strong>.',
    'access_type_cross' => 'All :source type institutions from the <strong>main tenant</strong> will be able to see :target type institution data <strong>in other tenants</strong> (and vice versa). This enables centralized oversight.',

    // Scope explanations
    'scope_within_tenant_explanation' => 'The relationship will only work between institutions belonging to the same tenant.',
    'scope_cross_tenant_explanation' => 'The relationship will work between the main tenant and other tenants\' institutions. Useful for centralized management.',

    // Direction labels
    'direction_outgoing' => 'Outgoing',
    'direction_incoming' => 'Incoming',
    'direction_sibling' => 'Sibling',

    // Authorization
    'authorized' => 'Authorized',
    'not_authorized' => 'Not authorized',
    'view_only' => 'View only',

    // Tooltip
    'tooltip_via' => 'Via relationship with',
    'tooltip_authorized' => 'Full data access',
    'tooltip_not_authorized' => 'Meeting view only (no agenda)',

    // Bidirectional settings
    'bidirectional' => 'Bidirectional relationship',
    'bidirectional_enabled' => 'Both sides can see each other\'s data',
    'bidirectional_disabled' => 'Only source can see target',
    'bidirectional_explanation' => 'When enabled, both sides of the relationship can see each other\'s institution data (meetings, information, etc.). When disabled, only the source sees the target.',
    'bidirectional_yes' => 'Bidirectional',
    'bidirectional_no' => 'Unidirectional',
    'access_bidirectional_note' => '↔ Both sides will be able to see each other\'s data.',
    'access_unidirectional_note' => '→ Only source will see target\'s data. Target will see the relationship but not the data.',

    // Validation
    'same_type_error' => 'Source and target cannot be the same type. Same-type sibling relationships are configured in the Type edit form using the "Show related institutions by type" setting.',
];
