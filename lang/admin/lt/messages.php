<?php

/*
 * Flash messages shown after an admin action.
 *
 * The four CRUD actions keep one variant per grammatical gender ('f' / 'm'), because the
 * Lithuanian participle agrees with the entity it describes ("naujiena sukurta", but
 * "puslapis sukurtas"). Controllers never pick the variant themselves — they call
 * App\Http\Controllers\AdminController::entityMessage(), which reads the gender from
 * entities.php.
 */

return [
    'created' => [
        'f' => ':Model sėkmingai sukurta.',
        'm' => ':Model sėkmingai sukurtas.',
    ],
    'updated' => [
        'f' => ':Model sėkmingai atnaujinta.',
        'm' => ':Model sėkmingai atnaujintas.',
    ],
    'deleted' => [
        'f' => ':Model sėkmingai ištrinta.',
        'm' => ':Model sėkmingai ištrintas.',
    ],
    'restored' => [
        'f' => ':Model sėkmingai atkurta.',
        'm' => ':Model sėkmingai atkurtas.',
    ],
    'users_attached_to_reservation' => 'Rezervacijos valdytojai pridėti!',

    'auth' => [
        'logout_success' => 'Sėkmingai atsijungėte.',
        'password_changed' => 'Slaptažodis sėkmingai pakeistas.',
        'login_cancelled' => 'Prisijungimas buvo atšauktas. Bandykite dar kartą, jei norite prisijungti.',
        'login_error' => 'Prisijungimo metu įvyko klaida. Bandykite dar kartą.',
        'login_failed' => 'Prisijungimas nepavyko. Bandykite dar kartą.',
        'login_unexpected_error' => 'Įvyko netikėta klaida. Bandykite dar kartą.',
        'duty_email_many_users' => 'Nepavyko prisijungti su pareigybiniu paštu, nes pareigybinis paštas turi daugiau nei vieną aktyvų vartotoją. Susisiekite su administratoriumi.',
        'duty_email_no_user' => 'Nepavyko prisijungti su pareigybiniu paštu, nes pareigybinis paštas neturi aktyvaus vartotojo. Bandykite ištrinti slapukus arba naudoti naršyklės privatų rėžimą.',
        'no_account_found' => 'Su šiuo el. pašto adresu nerastas nei vartotojas, nei pareigybė. Susisiekite su VU SA padalinio studentų atstovų koordinatoriumi ar administratoriumi, kad gautumėte prieigą.',
    ],

    'meeting' => [
        'created' => 'Posėdis sukurtas sėkmingai!',
        'create_failed' => 'Nepavyko sukurti posėdžio.',
        'updated' => 'Posėdis sėkmingai atnaujintas.',
        'deleted' => 'Posėdis ištrintas sėkmingai!',
        'restored' => 'Posėdis sėkmingai atkurtas.',
        'institution_attached' => 'Institucija sėkmingai pridėta prie posėdžio.',
        'calendar_event_created' => 'Sukurtas kalendoriaus įrašo juodraštis. Paskelbkite jį, kad posėdis taptų matomas viešai.',
        'calendar_event_linked' => 'Posėdis susietas su kalendoriaus įrašu.',
        'calendar_event_unlinked' => 'Posėdis atsietas nuo kalendoriaus įrašo. Pats įrašas nepašalintas.',
        'document_linked' => 'Dokumentas susietas su posėdžiu.',
        'document_unlinked' => 'Dokumentas atsietas nuo posėdžio.',
        'institution_detached' => 'Institucija sėkmingai pašalinta iš posėdžio.',
        'institution_required' => 'Posėdis turi turėti bent vieną instituciją.',
    ],

    'agenda_item' => [
        'created_many' => 'Darbotvarkės punktai sukurti sėkmingai!',
        'reordered' => 'Darbotvarkės punktų tvarka pakeista sėkmingai!',
        'notes_saved' => 'Pastabos išsaugotos.',
    ],

    'vote' => [
        'main_changed' => 'Pagrindinis balsavimas pakeistas sėkmingai!',
    ],

    'comment' => [
        'invalid_type' => 'Neleistinas komentaro tipas.',
        'model_not_found' => 'Modelis nerastas.',
    ],

    'document' => [
        'none_to_process' => 'Nėra dokumentų, kuriuos būtų galima apdoroti.',
        'stored' => 'Dokumentai sėkmingai išsaugoti.',
        'refresh_queued' => 'Dokumento atnaujinimas įtrauktas į eilę. Jis bus atnaujintas netrukus.',
        'bulk_sync_queued' => 'Į eilę įtrauktas :count dokumentų atnaujinimas. Jie bus atnaujinti netrukus.',
    ],

    'duty' => [
        'order_updated' => 'Pareigų tvarka sėkmingai atnaujinta!',
        'email_updated' => 'Pareigybės el. paštas sėkmingai atnaujintas!',
    ],

    'institution' => [
        'cannot_delete_own' => 'Negalima ištrinti institucijos, kurioje esate!',
    ],

    'navigation' => [
        'order_updated' => 'Navigacijos tvarka sėkmingai atnaujinta!',
    ],

    'news' => [
        'duplicated' => 'Naujiena sėkmingai nukopijuota!',
        'no_available_tenant' => 'Nėra prieinamo padalinio, kuriam galėtumėte sukurti naujieną.',
    ],

    'quick_link' => [
        'order_updated' => 'Greitųjų nuorodų tvarka sėkmingai atnaujinta!',
    ],

    'relationship' => [
        'model_relation_deleted' => 'Ryšys tarp modelių ištrintas.',
        'type_model_relation_deleted' => 'Ryšio tipas tarp modelių ištrintas.',
    ],

    'role' => [
        'not_editable' => 'Negalima redaguoti šios rolės.',
        'not_deletable' => 'Negalima ištrinti šios rolės.',
        'permissions_updated' => 'Rolės leidimai atnaujinti.',
        'attachables_updated' => 'Rolės galimos priklausomybės atnaujintos.',
        'duties_updated' => 'Rolės pareigos atnaujintos.',
        'not_assignable_to_duty' => 'Negalima priskirti šios rolės pareigybėms! Bandykite iš naujo.',
    ],

    'study_program' => [
        'merged' => 'Studijų programos sėkmingai sujungtos.',
        'in_use' => 'Negalima ištrinti studijų programos. Ji priskirta :count pareigybės laikotarpiui (-iams).',
    ],

    'task' => [
        'automatic_not_deletable' => 'Ši užduotis užsibaigia automatiškai ir negali būti ištrinta.',
        'automatic_not_markable' => 'Ši užduotis užsibaigia automatiškai ir negali būti pažymėta rankiniu būdu.',
        'status_updated' => 'Užduoties būsena sėkmingai atnaujinta.',
    ],

    'mail_queue' => [
        'item_deleted' => 'Laiško eilutė pašalinta iš eilės.',
        'recipient_cleared' => 'Pašalinta :count gavėjo laukiančių eilučių.',
        'cleared' => 'Laiškų eilė išvalyta – pašalinta :count eilutė (-ės).',
    ],

    'user' => [
        'merged' => 'Kontaktai sėkmingai sujungti!',
        'password_created' => 'Slaptažodis sėkmingai sukurtas!',
        'password_deleted' => 'Slaptažodis sėkmingai ištrintas!',
    ],

    'dashboard' => [
        'settings_saved' => 'Nustatymai išsaugoti.',
        'notification_settings_saved' => 'Pranešimų nustatymai išsaugoti.',
    ],

    'feedback' => [
        'thanks' => 'Ačiū už atsiliepimą!',
    ],

    'calendar' => [
        'image_deleted' => 'Nuotrauka ištrinta!',
    ],

    'sharepoint' => [
        'not_fileable' => 'Failas negali būti priskirtas objektui.',
        'fileable_missing' => 'Susijęs objektas neegzistuoja.',
        'fileable_not_allowed' => 'Susijęs objektas negali turėti failų.',
        'uploaded' => 'Failas sėkmingai įkeltas į Sharepoint!',
        'file_deleted' => 'Failas ištrintas.',
        'invalid_request' => 'Neteisinga užklausa. Praneškite administratoriui.',
        'deleted_locally_only' => 'Failas pažymėtas kaip ištrintas, bet SharePoint operacija nepavyko.',
    ],
];
