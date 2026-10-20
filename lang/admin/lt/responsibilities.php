<?php

return [
    'label' => 'Atsakomybės',

    'entity' => [
        'singular' => 'Atsakomybė',
        'plural' => 'Atsakomybės',
    ],

    'purposes' => [
        'student_representation' => [
            'label' => 'Studentų atstovavimas',
            'drives' => 'Jos nariai laikomi studentų atstovais: rodomi posėdžiuose, gauna užduotis, kai institucija neturi sekretoriaus, ir veiklos užklausas, jų darbas skaičiuojamas atstovų rodikliuose.',
        ],
        'student_rep_coordination' => [
            'label' => 'Studentų atstovų koordinavimas',
            'drives' => 'Į jos narius kreipiasi VU organų studentų atstovai; jie gauna pranešimus apie registracijas ir posėdžius, jų vardu pasirašomi laiškai.',
        ],
        'member_registration' => [
            'label' => 'Narių registracija',
            'drives' => 'Jos nariai gauna naujų narių registracijas savo padalinyje ir gali jas peržiūrėti.',
        ],
    ],

    'scopes' => [
        'tenant' => 'Visas padalinys',
        'institution_type' => 'Institucijų tipas',
        'institution' => 'Institucija',
    ],

    'sources' => [
        'tenant' => 'Priskirta visam padaliniui',
        'institution_type' => 'Priskirta institucijos tipui',
        'institution' => 'Priskirta šiai institucijai',
    ],

    'index' => [
        'lead' => 'Atsakomybė nusako, ką pareigybė daro, ir suteikia tam reikalingas teises. Ją suteiki pareigybei, pareigybės tipui ar visai institucijai.',
        'search' => 'Ieškoti atsakomybių',
        'create' => 'Nauja atsakomybė',
        'purpose' => 'Sistemos paskirtis',
        'access_only' => 'Prieiga',
        'sort_purpose' => 'Su paskirtimi pirmiau',
        'sort_people' => 'Daugiausia žmonių',
        'permissions' => ':count teisė|:count teisės|:count teisių',
        'duties' => ':count pareigybė|:count pareigybės|:count pareigybių',
        'people' => ':count žmogus|:count žmonės|:count žmonių',
    ],

    'form' => [
        'create' => 'Nauja atsakomybė',
        'what' => 'Kas tai?',
        'what_description' => 'Pavadink darbą, o ne žmogų: pvz., „Komunikacija“, „Organizacijos valdymas“.',
        'title_hint' => 'Rodomas sąrašuose ir pareigybės puslapyje.',
        'title_example' => 'pvz., Komunikacija',
        'description_hint' => 'Kokį darbą ši atsakomybė apima ir kam ji skirta.',
        'purpose_note' => 'Ši atsakomybė atlieka sistemos paskirtį „:purpose“. Ją keisti gali nustatymuose „Sistemos paskirtys“.',
    ],

    'page' => [
        'purpose' => 'Sistemos paskirtis: :purpose',
        'purpose_settings' => 'Keisti nustatymuose',
        'gaps' => ':count pareigybei trūksta teisių, kurių reikia šiam darbui.|:count pareigybėms trūksta teisių, kurių reikia šiam darbui.|:count pareigybių trūksta teisių, kurių reikia šiam darbui.',
        'super_admin' => 'Super administratorius gali viską; jo teisių keisti nereikia.',
        'delete_title' => 'Šalinti atsakomybę?',
        'delete_description' => 'Atsakomybė bus atimta iš visų pareigybių, kurioms ji suteikta, o jų nariai neteks jos teisių.',
    ],

    'tabs' => [
        'permissions' => 'Teisės',
        'grants' => 'Kam suteikta',
        'people' => 'Žmonės',
        'delegation' => 'Delegavimas',
    ],

    'facts' => [
        'permissions' => 'Teisės',
        'duties' => 'Pareigybės',
        'people' => 'Žmonės dabar',
        'direct' => ':count tiesiogiai|:count tiesiogiai|:count tiesiogiai',
        'via_types' => ':count per tipą|:count per tipus|:count per tipų',
        'via_institutions' => ':count per instituciją|:count per institucijas|:count per institucijų',
    ],

    'matrix' => [
        'intro' => 'Kiekvienam veiksmui pasirink, kur jis galioja: savo įrašuose, visame padalinyje ar visur. Užrakintus veiksmus visi nariai jau turi.',
        'search' => 'Ieškoti srities',
        'only_granted' => 'Tik suteiktos',
        'nothing_found' => 'Tokių sričių nėra.',
        'resource' => 'Sritis',
        'everyone' => 'Visi nariai',
        'not_applicable' => 'Netaikoma',
        'discard' => 'Atšaukti',
        'changes' => ':added pridėta, :removed atimta|:added pridėta, :removed atimta|:added pridėta, :removed atimta',
        'legacy_title' => 'Seni leidimai',
        'legacy_description' => 'Šių leidimų sistema nebetikrina. Jie lieka, kol juos pašalins administratorius.',
        'abilities' => [
            'read' => 'Matyti',
            'create' => 'Kurti',
            'update' => 'Redaguoti',
            'delete' => 'Trinti',
            'forceDelete' => 'Ištrinti negrįžtamai',
        ],
        'scopes' => [
            'own' => 'Savo',
            'padalinys' => 'Padalinyje',
            'all' => 'Visur',
        ],
        'resources' => [
            'settings' => 'Nustatymai',
        ],
    ],

    'grants' => [
        'intro' => 'Dažniausiai atsakomybę suteiki pareigybei. Pareigybės tipui suteikta atsakomybė atitenka visoms to tipo pareigybėms, institucijai – visoms jos pareigybėms.',
        'duties' => 'Pareigybės',
        'duties_empty' => 'Tiesiogiai nesuteikta jokiai pareigybei.',
        'duty_types' => 'Pareigybių tipai',
        'duty_types_hint' => 'Kiekviena šio tipo pareigybė gauna šią atsakomybę automatiškai.',
        'duty_types_empty' => 'Nesuteikta jokiam tipui.',
        'institutions' => 'Institucijos',
        'institutions_hint' => 'Retas atvejis: atsakomybę gauna kiekviena institucijos pareigybė, pvz., visiems Centrinio biuro nariams.',
        'add' => 'Suteikti',
        'add_type' => 'Suteikti tipui',
        'add_institution' => 'Suteikti institucijai',
        'remove' => 'Atimti',
        'remove_confirm' => 'Atimti atsakomybę iš „:name“?',
        'remove_description' => 'Šios atsakomybės teisių neteks visi, kurie ją gauna tik per šį suteikimą.',
        'holders' => ':count narys|:count nariai|:count narių',
        'reaches' => 'Gauna :count pareigybė|Gauna :count pareigybės|Gauna :count pareigybių',
        'missing' => 'Šiam darbui trūksta: :permissions',
        'sheet_title' => 'Suteikti atsakomybę',
        'sheet_description' => 'Pasirink, kas gaus šią atsakomybę ir jos teises.',
        'to' => 'Kam',
        'to_duty' => 'Pareigybei',
        'to_duty_type' => 'Tipui',
        'to_institution' => 'Institucijai',
        'duty_type' => 'Pareigybės tipas',
        'institution' => 'Institucija',
        'scope_hint' => 'Kam ši pareigybė atsakinga: konkretesnis suteikimas nusveria bendresnį.',
        'submit' => 'Suteikti',
    ],

    'type_page' => [
        'manage' => 'Tvarkyti atsakomybes',
    ],

    'people' => [
        'title' => 'Žmonės dabar',
        'description' => 'Visi, kurie šiuo metu eina pareigybes su šia atsakomybe.',
        'empty' => 'Šiuo metu šios atsakomybės niekas neturi.',
    ],

    'delegation' => [
        'title' => 'Gali priskirti pareigybių tipus',
        'description' => 'Šios atsakomybės nariai pareigybės formoje gali priskirti pažymėtus tipus.',
    ],

    'duty' => [
        'title' => 'Atsakomybės',
        'description' => 'Ką ši pareigybė daro. Atsakomybės suteikia ir tam reikalingas teises.',
        'empty' => 'Atsakomybių nėra',
        'add' => 'Pridėti atsakomybę',
        'remove' => 'Pašalinti',
        'remove_confirm' => 'Pašalinti atsakomybę „:name“?',
        'via_duty_type' => 'Per tipą „:name“',
        'via_institution' => 'Per instituciją „:name“',
    ],

    'sheet' => [
        'title' => 'Nauja atsakomybė',
        'description' => 'Pareigybės nariai gaus atsakomybę ir jos teises. Kai atsakomybė turi sistemos paskirtį, nurodyk, už ką jie atsakingi.',
        'responsibility' => 'Atsakomybė',
        'only_yours' => 'Gali suteikti tik tas atsakomybes, kurias turi pats.',
        'scope' => 'Kam',
        'tenant' => 'Padalinys',
        'institution_type' => 'Institucijų tipas',
        'type' => 'Institucijų tipas',
        'type_placeholder' => 'Pasirinkti tipą',
        'institution' => 'Institucija',
        'institution_pick' => 'Pasirinkti instituciją',
        'institution_change' => 'Keisti',
        'submit' => 'Pridėti',
    ],

    'attention' => [
        'no_coordinator' => 'Padaliniai be studentų atstovų koordinatoriaus',
        'no_coordinator_empty' => 'Visi padaliniai turi studentų atstovų koordinatorių',
        'no_coordinator_hint' => 'Studentų atstovai čia neturi į ką kreiptis, o pranešimai apie registracijas niekam nesiunčiami. Suteik koordinavimo atsakomybę koordinatoriaus pareigybei.',
    ],

    'spotlight' => [
        'title' => 'Rolės dabar – atsakomybės',
        'body' => 'Čia nurodai, ką pareigybė daro. Atsakomybė kartu suteikia ir teises, todėl atskirų rolių nebėra.',
    ],

    'messages' => [
        'added' => 'Atsakomybė suteikta.',
        'removed' => 'Atsakomybė atimta.',
        'permissions_saved' => 'Teisės išsaugotos: :added pridėta, :removed atimta.',
        'minimum_unmet' => 'Kai kurioms pareigybėms dabar trūksta teisių, kurių reikia šiam darbui.',
        'purpose_blocks_delete' => 'Šios atsakomybės ištrinti negalima: ji atlieka sistemos paskirtį „:purpose“. Pirmiau ją perkelk nustatymuose „Sistemos paskirtys“.',
        'list_blocks_delete' => 'Šios atsakomybės ištrinti negalima: pagal ją sudaromos konferencijos :lists.',
    ],

    'validation' => [
        'not_grantable' => 'Šios atsakomybės suteikti negali: ją gali suteikti tik ją turintys arba atsakomybes valdantys nariai.',
        'scope_not_allowed' => 'Šiai atsakomybei toks priskyrimas negalimas.',
        'target_out_of_reach' => 'Šio padalinio, institucijos ar tipo tvarkyti negali.',
        'self_lockout' => 'Taip atimtum iš savęs galimybę tvarkyti atsakomybes. Paprašyk kito administratoriaus.',
    ],
];
