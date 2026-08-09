<?php

return [
    'title' => 'Apklausos',
    'question_bank' => 'Klausimų bankas',
    'default_group' => 'Klausimai',

    'status' => [
        'draft' => 'Juodraštis',
        'pending_approval' => 'Laukia patvirtinimo',
        'approved' => 'Patvirtinta',
        'rejected' => 'Atmesta',
        'active' => 'Vykdoma',
        'closed' => 'Baigta',
    ],

    'question_type' => [
        'short_text' => 'Trumpas tekstas',
        'long_text' => 'Ilgas tekstas',
        'list' => 'Vienas pasirinkimas',
        'multiple_choice' => 'Keli pasirinkimai',
        'five_point' => 'Vertinimas 1–5',
    ],

    'fields' => [
        'status' => 'Būsena',
        'name' => 'Pavadinimas',
        'description' => 'Aprašymas',
        'welcome_text' => 'Pasisveikinimo tekstas',
        'starts_at' => 'Pradžia',
        'ends_at' => 'Pabaiga',
        'is_anonymous' => 'Anoniminė apklausa',
        'tenant' => 'Padalinys',
        'group_name' => 'Klausimų grupė',
        'title' => 'Klausimo kodas',
        'type' => 'Tipas',
        'question' => 'Klausimas',
        'help' => 'Pagalbos tekstas',
        'is_required' => 'Privalomas',
        'options' => 'Atsakymų variantai',
        'option_code' => 'Kodas',
        'option_label' => 'Tekstas',
        'is_active' => 'Aktyvus',
    ],

    'global_template' => 'Bendras',

    'helpers' => [
        'anonymous' => 'LimeSurvey nesaugos atsakymų sąsajos su respondentu.',
        'question_code' => 'Trumpas kodas, naudojamas LimeSurvey rezultatų stulpelyje (pvz. Q01).',
        'no_questions_yet' => 'Klausimų dar nėra. Pridėkite iš banko arba sukurkite naują.',
        'locked' => 'Apklausa publikuota, todėl klausimų keisti nebegalima.',
        'global_template' => 'Palikite tuščią, kad šablonas būtų prieinamas visiems padaliniams.',
    ],

    'sections' => [
        'questions' => 'Klausimai',
        'approval' => 'Patvirtinimas',
        'limesurvey' => 'LimeSurvey',
    ],

    'actions' => [
        'create' => 'Nauja apklausa',
        'add_question' => 'Pridėti klausimą',
        'add_from_template' => 'Pridėti iš banko',
        'save_questions' => 'Išsaugoti klausimus',
        'request_approval' => 'Teikti tvirtinti',
        'resync' => 'Atnaujinti iš LimeSurvey',
        'retry_publish' => 'Bandyti publikuoti dar kartą',
        'open_survey' => 'Atidaryti apklausą',
        'add_option' => 'Pridėti variantą',
    ],

    'limesurvey' => [
        'not_configured' => 'LimeSurvey integracija nesukonfigūruota.',
        'not_published' => 'Apklausa dar nepublikuota LimeSurvey sistemoje.',
        'survey_id' => 'LimeSurvey ID',
        'public_url' => 'Vieša nuoroda',
        'sync_status' => 'Sinchronizacijos būsena',
        'last_synced' => 'Paskutinį kartą atnaujinta',
        'completed' => 'Užpildyta',
        'incomplete' => 'Pradėta',
        'full' => 'Iš viso',
        'locked_notice' => 'Apklausa jau publikuota — LimeSurvey neleidžia keisti jos struktūros.',
    ],

    'validation' => [
        'duplicate_titles' => 'Klausimų kodai turi būti unikalūs.',
        'options_required' => 'Šis klausimo tipas reikalauja bent vieno atsakymo varianto.',
    ],

    'flash' => [
        'created' => 'Apklausa sukurta.',
        'updated' => 'Apklausa atnaujinta.',
        'deleted' => 'Apklausa ištrinta.',
        'questions_saved' => 'Klausimai išsaugoti.',
        'approval_requested' => 'Apklausa pateikta tvirtinti.',
        'no_questions' => 'Apklausa neturi klausimų.',
        'no_flow' => 'Nerastas apklausų tvirtinimo procesas. Paleiskite ApprovalFlowSeeder.',
        'not_approved' => 'Apklausa dar nepatvirtinta.',
        'publish_queued' => 'Publikavimas įtrauktas į eilę.',
        'stats_queued' => 'Statistikos atnaujinimas įtrauktas į eilę.',
        'template_created' => 'Klausimo šablonas sukurtas.',
        'template_updated' => 'Klausimo šablonas atnaujintas.',
        'template_deleted' => 'Klausimo šablonas ištrintas.',
    ],
];
