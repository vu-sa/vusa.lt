<?php

return [
    'filters' => [
        'all' => 'Visi',
        'completed' => 'Užbaigti',
        'incomplete' => 'Nepabaigti',
        'label' => 'Žymė',
        'select_filter' => 'Pasirinkite filtrą',
    ],
    'create_new' => 'Sukurti naują',
    'due' => 'Terminas',
    'auto_completing' => 'Automatiškai užbaigiama',
    'instructions' => 'Instrukcijos',
    'available_actions' => 'Galimi veiksmai',
    'assigned_to' => 'Priskirta',
    'stats' => [
        'pending' => 'Nebaigtos užduotys',
        'pending_caption' => 'Dar neatliktos',
        'auto_completing' => 'Automatinės',
        'auto_completing_caption' => 'Užbaigia sistema',
        'overdue_caption' => 'Praleistas terminas',
        'completed_caption' => 'Atliktos',
    ],
    'periodicity_gap' => [
        'name' => 'Pranešti apie :institution veiklą',
        'description' => 'Institucijos veiklos pranešimo periodiškumas artėja prie ribos. Užregistruokite naują susitikimą arba praneškite apie veiklą.',
        'completed_meeting_created' => 'Susitikimas užregistruotas',
        'completed_checkin_created' => 'Pranešimas apie veiklą sukurtas',
        'schedule_meeting' => 'Registruoti susitikimą',
        'report_no_meeting' => 'Pranešti apie veiklą',
        'action_schedule_meeting' => 'Registruoti susitikimą',
        'action_report_no_meeting' => 'Pranešti apie veiklą',
    ],
    'agenda_creation' => [
        'meeting_context' => 'Posėdis: :institution (:date).',
        'assignee_context' => 'Jūs ir dar :count asmuo(-ų) turi šią užduotį.',
        'first_item_created' => 'Pirmas darbotvarkės klausimas sukurtas',
    ],
    'agenda_completion' => [
        'meeting_context' => 'Posėdis: :institution (:date).',
        'assignee_context' => 'Jūs ir dar :count asmuo(-ų) turi šią užduotį.',
        'all_items_completed' => 'Visi darbotvarkės klausimai užpildyti',
    ],
    'delete_automatic' => 'Ištrinti (administratoriaus teisėmis)',
    'delete_confirm_title' => 'Ištrinti šią užduotį?',
    'delete_confirm_description' => '„:name" bus visam laikui pašalinta. Šio veiksmo atšaukti negalėsite.',
    'orphaned' => 'Objektas ištrintas',
    'orphaned_description' => 'Įrašas, kuriam priskirta ši užduotis, nebeegzistuoja, todėl užduotis niekada nebus užbaigta automatiškai.',
    'agenda' => [
        'action_add_items' => 'Pridėti darbotvarkės klausimą',
        'action_view_agenda' => 'Peržiūrėti darbotvarkę',
    ],
];
