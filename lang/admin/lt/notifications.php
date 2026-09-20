<?php

return [
    // Categories
    'categories' => [
        'comment' => 'Komentarai',
        'task' => 'Užduotys',
        'reservation' => 'Rezervacijos',
        'meeting' => 'Posėdžiai',
        'registration' => 'Registracijos',
        'user' => 'Vartotojas',
        'duty' => 'Pareigos',
        'system' => 'Sistema',
        'news' => 'Naujienos',
        'calendar' => 'Renginiai',
    ],

    // Channels
    'channels' => [
        'in_app' => 'Programėlėje',
        'in_app_description' => 'Gauti pranešimus tiesiogiai programėlėje',
        'push' => 'Push pranešimai',
        'push_description' => 'Gauti pranešimus į naršyklę / įrenginį',
        'push_enable' => 'Įjungti',
        'push_disable' => 'Išjungti',
        'push_blocked' => 'Užblokuota',
        'email_digest' => 'El. paštas',
        'email_digest_description' => 'Skubūs pranešimai ateina iškart, kiti – el. pašto santraukoje',
    ],

    // Digest
    'digest_subject' => '{1} Turi :count naują pranešimą|[2,9] Turi :count naujus pranešimus|[10,*] Turi :count naujų pranešimų',
    'digest_greeting' => 'Labas, :name!',
    'digest_intro' => 'Štai tavo pranešimų santrauka (:count pranešimai):',
    'digest_intro_short' => 'Štai tavo pranešimų santrauka:',
    'digest_footer' => 'Gali keisti pranešimų nustatymus savo profilyje.',
    'digest_count_label' => '{1} pranešimas|[2,9] pranešimai|[10,*] pranešimų',
    'digest_total' => 'iš viso',
    'digest_more_items' => '{1} dar :count pranešimas|[2,9] dar :count pranešimai|[10,*] dar :count pranešimų',
    'digest_open_system' => 'Atidaryti Mano VU SA',
    'digest_view_all' => 'Peržiūrėti visus pranešimus',
    'view_all' => 'Rodyti visus pranešimus',
    'toast_fallback_title' => 'Naujas pranešimas',

    // Comment notifications
    'comment_posted_title' => 'Naujas komentaras: :name',
    'comment_posted_body' => ':commenter paliko komentarą: :comment',
    'commented_on' => 'pakomentavo',
    'changed_status_on' => 'pakeitė statusą',
    'left_comment_on' => 'paliko komentarą',
    'mentioned_you_in_comment' => 'paminėjo tave komentare',
    'started_poll_on' => 'pradėjo apklausą',

    // Assignment notifications
    'assigned_to_resource_title' => 'Priskirta prie: :resource',
    'assigned_to_resource_body' => ':assigner priskyrė tave prie :resource',
    'assigned_to_resource_greeting' => 'Buvai priskirtas (-a)',
    'assigned_you_to' => 'priskyrė tave prie',

    // Task notifications
    'task_assigned_title' => 'Nauja užduotis',
    'task_assigned_body' => 'Tau priskirta nauja užduotis: :task',
    'task_assigned_body_with_assigner' => ':assigner priskyrė tau užduotį: :task',
    'task_reminder_title' => 'Užduoties priminimas (:days d.)',
    'task_reminder_body' => 'Liko :days d. iki užduoties „:task" termino',
    'task_completed_title' => 'Užduotis atlikta',
    'task_completed_body' => ':user atliko užduotį „:task"',
    'task_auto_completed_title' => 'Užduotis automatiškai atlikta',
    'task_auto_completed_body' => 'Užduotis „:task" buvo automatiškai atlikta: :reason',
    'task_auto_completed_body_with_user' => 'Užduotis „:task" buvo automatiškai atlikta (:user): :reason',
    'task_overdue_title' => '{1} Turi :count vėluojančią užduotį|[2,9] Turi :count vėluojančias užduotis|[10,*] Turi :count vėluojančių užduočių',
    'task_overdue_body_single' => 'Užduotis „:task" yra vėluojanti',
    'task_overdue_body_multiple' => 'Turi :count vėluojančias užduotis: :tasks ir kt.',
    'overdue_tasks' => 'Vėluojančios užduotys',
    'periodicity_gap_title' => 'Laikas pranešti apie :institution veiklą',
    'periodicity_gap_body' => 'Institucijai „:institution“ užregistruok naują posėdį arba pranešk apie veiklą.',
    'action_register_meeting' => 'Registruoti posėdį',
    'action_report_activity' => 'Pranešti apie veiklą',

    // Registration notifications
    'member_registered_title' => 'Nauja nario registracija',
    'member_registered_body' => ':name užsiregistravo į :institution',
    'student_rep_registered_title' => 'Nauja atstovo registracija',
    'student_rep_registered_body' => ':name užsiregistravo kaip atstovas: :institution',

    // Reservation notifications
    'reservation_status_changed_title' => 'Rezervacijos statusas: :status',
    'reservation_status_changed_body' => 'Rezervacija „:reservation" (:resource) pakeista į :status',
    'reservation_status_changed_body_with_user' => ':user pakeitė rezervacijos „:reservation" (:resource) statusą į :status',
    'reservation_task_hint_pickup' => '📋 Atsimink :resource iki :date.',
    'reservation_task_hint_return' => '📋 Grąžink :resource iki :date.',

    // Approval notifications
    'approval_requested_title' => 'Reikalingas patvirtinimas',
    'approval_requested_body' => 'Reikia tavo patvirtinimo: :item',
    'approval_approved_title' => 'Patvirtinta',
    'approval_approved_body' => ':user patvirtino :item',
    'approval_rejected_title' => 'Atmesta',
    'approval_rejected_body' => ':user atmetė :item',
    'approval_cancelled_title' => 'Atšaukta',
    'approval_cancelled_body' => ':user atšaukė :item',
    'approval_escalation_title' => 'Patvirtinimas vėluoja',
    'approval_escalation_body' => ':item patvirtinimas vėluoja ir reikalauja tavo dėmesio',

    // Meeting notifications
    'meeting_reminder_title' => 'Artėjantis posėdis',
    'meeting_reminder_soon_title' => 'Posėdis netrukus!',
    'meeting_reminder_body' => 'Po :hours val. vyks posėdis: :meeting',
    'meeting_reminder_body_one_hour' => 'Po 1 valandos vyks posėdis: :meeting',
    'meeting_created_title' => 'Sukurtas naujas posėdis',
    'meeting_created_body' => 'Sukurtas naujas :institution posėdis, vyks :date',
    'meeting_agenda_completed_title' => 'Posėdžio darbotvarkė užpildyta',
    'meeting_agenda_completed_body' => 'Visi :count :institution posėdžio darbotvarkės klausimai užpildyti',
    'meeting_agenda_completed_body_with_user' => ':user užpildė visus :count :institution posėdžio darbotvarkės klausimus',
    'meeting_agenda_type_voting' => ':count balsavimu',
    'meeting_agenda_type_informational' => ':count informaciniu',
    'meeting_agenda_type_deferred' => ':count atidėtu',
    'meeting_agenda_type_break' => ':count pertrauka',
    'meeting_agenda_additional_votes_note' => 'Jei buvo papildomų balsavimų, nepamiršk jų įtraukti.',

    // News notifications
    'news_published_title' => 'Naujas straipsnis',
    'news_published_body' => ':tenant paskelbė naują straipsnį: :title',

    // Calendar notifications
    'calendar_reminder_title' => 'Artėjantis renginys',
    'calendar_reminder_soon_title' => 'Renginys netrukus!',
    'calendar_reminder_body' => 'Maždaug po :hours val. vyks renginys: :event',
    'calendar_reminder_body_one_hour' => 'Maždaug po 1 valandos vyks renginys: :event',
    'calendar_reminder_body_tomorrow' => 'Rytoj vyks renginys: :event',

    // Duty notifications
    'duty_expiring_title' => 'Pareigos baigiasi po :days d.',
    'duty_expiring_body' => 'Tavo pareigybė „:duty" baigiasi :date. Nepamiršk perduoti patirties!',

    // System notifications
    'welcome_title' => 'Sveiki atvykę į Mano VU SA! 🎉',
    'welcome_body' => 'Puiku, :name! Baigei pirmą pažintį su Mano VU SA. Smagaus naudojimosi!',
    'test_notification_title' => 'Bandomasis pranešimas',
    'test_notification_body' => 'Tai yra bandomasis pranešimas! Push pranešimai veikia.',

    // Test email
    'test_email_button' => 'Siųsti bandomąjį laišką',
    'test_email_hint' => 'Laiškas bus išsiųstas išsaugotais adresais. Pirma išsaugokite pakeitimus.',
    'test_email_sent' => 'Bandomasis laiškas išsiųstas: :emails',
    'test_email_failed' => 'Nepavyko išsiųsti laiško: :error',

    // Misc UI labels
    'category' => 'Kategorija',
    'mark_as_read' => 'Pažymėti kaip skaitytą',

    // Actions
    'action_view_comment' => 'Peržiūrėti komentarą',
    'action_view_resource' => 'Peržiūrėti išteklių',
    'action_view_tasks' => 'Peržiūrėti užduotis',
    'action_view_registration' => 'Peržiūrėti registraciją',
    'action_view_reservation' => 'Peržiūrėti rezervaciją',
    'action_view_meeting' => 'Peržiūrėti posėdį',
    'action_view_duty' => 'Peržiūrėti pareigas',
    'action_explore_dashboard' => 'Atidaryti Mano VU SA',
    'action_view' => 'Peržiūrėti',
    'action_review' => 'Peržiūrėti',

    // Email footer: signature and "why you got this"
    'mail' => [
        'why_received' => 'Gavai šį laišką, nes pranešimų nustatymuose įjungei kategorijos „:category“ laiškus.',
        'settings_link' => 'Keisti pranešimų nustatymus',
        'signature_intro' => 'Klausimų? Rašyk:',
        'sign_off' => 'Mano VU SA',
    ],

    // Context rows (label/value pairs shown under a notification)
    'context' => [
        'institution' => 'Institucija',
        'deadline' => 'Terminas',
        'days_overdue' => 'Vėluoja',
        'days_since_activity' => 'Be veiklos',
        'days_value' => ':count d.',
        'task' => 'Užduotis',
        'date' => 'Laikas',
        'format' => 'Formatas',
        'object' => 'Objektas',
        'step' => 'Etapas',
        'assigned_by' => 'Priskyrė',
        'duty' => 'Pareigybė',
        'end_date' => 'Kadencija baigiasi',
        'author' => 'Autorius',
    ],

    // Preferences UI
    'preferences' => [
        'title' => 'Pranešimų nustatymai',
        'description' => 'Pasirink, kaip nori gauti pranešimus ir kada.',
        'mute_all' => 'Laikinas išjungimas',
        'mute_all_description' => 'Laikinai išjungti visus pranešimus',
        'mute' => 'Išjungti...',
        'muted_until' => 'Nutildyta iki :date',
        'unmute' => 'Įjungti',
        'digest_frequency' => 'Santraukos dažnumas',
        'digest_frequency_description' => 'Kaip dažnai siųsti el. pašto santrauką',
        'digest_emails' => 'Santraukos el. pašto adresai',
        'digest_emails_description' => 'Pasirink, kuriais el. pašto adresais nori gauti pranešimų santrauką.',
        'digest_emails_default_info' => 'Nepasirinkus jokio adreso, santrauka bus siunčiama į tavo pareigybės el. paštą (jei toks yra) arba į asmeninį el. paštą.',
        'personal_email' => 'asmeninis',
        'duty_email' => 'pareigybės',
        'category_settings' => 'Pranešimų kategorijos',
        'category_settings_description' => 'Pasirink, kuriuos pranešimų tipus nori gauti kiekvienu kanalu.',
        'reminder_settings' => 'Priminimų nustatymai',
        'task_reminder_days' => 'Užduočių priminimo dienos',
        'task_reminder_days_description' => 'Prieš kiek dienų priminti apie artėjančių užduočių terminus',
        'meeting_reminder_hours' => 'Posėdžių priminimo valandos',
        'meeting_reminder_hours_description' => 'Prieš kiek valandų priminti apie artėjančius posėdžius',
        'calendar_reminder_hours' => 'Renginių priminimo valandos',
        'calendar_reminder_hours_description' => 'Prieš kiek valandų priminti apie artėjančius renginius',
    ],

    // Legacy compatibility
    'preferences_title' => 'Pranešimų nustatymai',
    'preferences_description' => 'Pasirink, kaip nori gauti pranešimus',
    'digest_frequency' => 'El. pašto santraukos dažnumas',
    'digest_frequency_1' => 'Kas 1 valandą',
    'digest_frequency_4' => 'Kas 4 valandas',
    'digest_frequency_12' => 'Kas 12 valandų',
    'digest_frequency_24' => 'Kartą per dieną',
    'mute_all' => 'Išjungti visus pranešimus',
    'mute_for_1h' => 'Išjungti 1 valandai',
    'mute_for_4h' => 'Išjungti 4 valandoms',
    'mute_until_tomorrow' => 'Išjungti iki rytojaus',
    'unmute' => 'Įjungti pranešimus',
    'muted_until' => 'Pranešimai išjungti iki :time',
    'mute_thread' => 'Nutildyti šią temą',
    'unmute_thread' => 'Įjungti šios temos pranešimus',

    // Reminder settings
    'reminder_settings' => 'Priminimų nustatymai',
    'task_reminder_days' => 'Užduočių priminimo dienos',
    'task_reminder_days_description' => 'Prieš kiek dienų priminti apie artėjančias užduotis',
    'meeting_reminder_hours' => 'Posėdžių priminimo valandos',
    'meeting_reminder_hours_description' => 'Prieš kiek valandų priminti apie artėjančius posėdžius',

    // Notification catalog
    'catalog_title' => 'Pranešimų katalogas',
    'catalog_description' => 'Visi sistemos siunčiami pranešimai',

    // Notifications index page
    'index' => [
        'filter_unread' => 'Neskaityti',
        'actions' => 'Veiksmai',
        'mark_all_read' => 'Pažymėti visus kaip skaitytus',
        'delete_read' => 'Ištrinti perskaitytus',
        'delete_all' => 'Ištrinti visus',
        'empty_unread_title' => 'Naujų pranešimų nėra',
        'empty_all_title' => 'Pranešimų nėra',
        'empty_unread_body' => 'Visus pranešimus perskaitėte! Nauji pranešimai bus rodomi čia.',
        'empty_all_body' => 'Kol kas pranešimų nėra. Jie bus rodomi čia, kai gausite naujų.',
        'deleted' => 'Pranešimas ištrintas.',
        'all_marked_read' => 'Visi pranešimai pažymėti kaip perskaityti.',
        'read_deleted' => 'Perskaityti pranešimai ištrinti.',
        'all_deleted' => 'Visi pranešimai ištrinti.',
    ],

    // Push device management UI
    'push_devices' => [
        'title' => 'Push pranešimų įrenginiai',
        'description' => 'Valdyk įrenginius, kuriuose įjungti push pranešimai.',
        'this_device' => 'Šis įrenginys',
        'not_supported' => 'Nepalaikoma',
        'blocked_in_browser' => 'Užblokuota naršyklėje',
        'all_devices' => 'Visi įrenginiai',
        'no_devices' => 'Nėra įrenginių su įjungtais push pranešimais',
        'unknown_device' => 'Nežinomas įrenginys',
        'current_badge' => 'Šis',
        'added_at' => 'Pridėta',
        'send_test' => 'Siųsti bandomąjį pranešimą',
        'test_sent' => 'Bandomasis pranešimas išsiųstas!',
        'status_not_supported' => 'Push pranešimai nepalaikomi šioje naršyklėje',
        'status_blocked' => 'Push pranešimai užblokuoti naršyklės nustatymuose',
        'status_enabled' => 'Push pranešimai įjungti',
        'status_disabled' => 'Push pranešimai išjungti',
    ],
];
