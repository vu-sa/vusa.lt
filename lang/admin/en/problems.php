<?php

return [
    'hints' => [
        'title' => 'A short, specific problem title, e.g. "Missing assessment procedure description".',
        'description' => 'Describe the essence of the problem: what happened, where, when, and who was affected.',
        'tenant' => 'The unit where the problem was registered. The institutions list is filtered by it.',
        'status' => 'Open — not yet addressed; In progress — currently being solved; Resolved — completed.',
        'occurred_at' => 'The date the problem occurred or was noticed.',
        'resolved_at' => 'Fill in only once the problem has been resolved.',
        'responsible_user' => 'The person responsible for solving the problem. Search by name.',
        'categories' => 'Pick one or more categories — each option shows a description to help you choose.',
        'institutions' => 'Institutions (e.g. faculty council, SPC) the problem relates to.',
        'steps_taken' => 'What has already been done: conversations, letters, meetings and their outcomes.',
        'solution' => 'The final solution to the problem. May be left empty and filled in later.',
    ],
    'validation' => [
        'title_required' => 'The problem title must be provided in at least one language.',
        'description_required' => 'The problem description must be provided in at least one language.',
        'tenant_required' => 'The tenant is required.',
        'tenant_exists' => 'The selected tenant is invalid.',
        'occurred_at_required' => 'The occurred date is required.',
        'resolved_at_after' => 'The resolved date must be after or equal to the occurred date.',
        'status_in' => 'The selected status is invalid.',
        'categories_exist' => 'One or more selected categories are invalid.',
        'institutions_exist' => 'One or more selected institutions are invalid.',
    ],
];
