<?php

return [
    'show_deleted' => 'Rodyti ištrintus',
    'deleted_only' => 'Tik ištrinti',
    'active_records' => 'Aktyvūs',
    'deleted_records' => 'Ištrinti',
    'showing_deleted_only' => 'Rodomi tik ištrinti įrašai.',
    'showing_deleted_only_description' => 'Šie įrašai yra ištrinti. Juos galima atkurti arba ištrinti negrįžtamai.',
    'exit_trash_view' => 'Išeiti iš ištrintų įrašų rodinio',
    'no_deleted_records' => 'Ištrintų įrašų nėra',
    'spotlight_title' => 'Ištrintų įrašų rodinys jau veikia',
    'spotlight_description' => 'Šiuo jungikliu peržiūrėk ištrintus įrašus, atkurk juos arba prireikus ištrink negrįžtamai.',

    'restore' => 'Atkurti',
    'restore_conflict' => 'Įrašo atkurti nepavyko — kol jis buvo ištrintas, jo reikšmę užėmė kitas įrašas.',
    'restored' => 'Įrašas sėkmingai atkurtas!',

    'cancel' => 'Atšaukti',
    'permanently_delete' => 'Ištrinti negrįžtamai',
    'permanently_deleted' => 'Įrašas negrįžtamai ištrintas.',
    'permanently_delete_title' => 'Ištrinti negrįžtamai?',
    'permanently_delete_description' => 'Įrašas bus negrįžtamai ištrintas. Šio veiksmo atšaukti nepavyks.',
    'must_be_deleted_first' => 'Negrįžtamai ištrinti galima tik jau ištrintus įrašus.',

    'blocked' => [
        'generic' => 'Šio įrašo negalima ištrinti negrįžtamai — su juo susieta: :blockers. Šie duomenys turi išlikti, todėl įrašas lieka ištrintas.',
        'has_related_records' => 'Įrašo negalima ištrinti negrįžtamai, nes su juo yra susietų kitų įrašų.',
        'duty_has_membership_history' => 'Pareigybės negalima ištrinti negrįžtamai — su ja susieta narystės istorija (:count įr.), kurią būtina išsaugoti. Pareigybė lieka ištrinta.',
    ],

    // Referenced-record labels used by trash.blocked.generic. Models that already have
    // an entities.*.model pluralisation reuse that instead of appearing here.
    'blockers' => [
        'check_ins' => '{1} veiklos žyma|[2,9] veiklos žymos|[10,*] veiklos žymų',
        'registrations' => '{1} registracija|[2,9] registracijos|[10,*] registracijų',
        'comments' => '{1} komentaras|[2,9] komentarai|[10,*] komentarų',
        'training_participation' => '{1} mokymų dalyvis|[2,9] mokymų dalyviai|[10,*] mokymų dalyvių',
        'membership_history' => '{1} narystės įrašas|[2,9] narystės įrašai|[10,*] narystės įrašų',
        'type_assignments' => '{1} tipo priskyrimas|[2,9] tipo priskyrimai|[10,*] tipo priskyrimų',
        'organised_trainings' => '{1} organizuojamas mokymas|[2,9] organizuojami mokymai|[10,*] organizuojamų mokymų',
        'primary_institution_of_tenant' => '{1} padalinys, kuriam ji yra pagrindinė institucija|[2,*] padaliniai, kuriems ji yra pagrindinė institucija',
        'reported_problems' => '{1} užregistruota problema|[2,9] užregistruotos problemos|[10,*] užregistruotų problemų',
    ],

    // One extra sentence per model in the delete / permanent-delete dialogs, resolved
    // by model name. Models without an entry simply get the generic wording.
    'notes' => [
        'duties' => [
            'delete' => 'Narių tarnybos istorija išlieka, o pareigybę galima atkurti kitą kadenciją.',
        ],
        'meetings' => [
            'delete' => 'Darbotvarkė, balsavimai ir sprendimai išsaugomi ir grįžta atkūrus posėdį.',
            'force_delete' => 'Kartu negrįžtamai dings darbotvarkė, balsavimai ir sprendimai.',
        ],
        'tags' => [
            'delete' => 'Naujienos žymą išlaiko ir atgaus ją atkūrus.',
            'force_delete' => 'Žyma bus pašalinta nuo visų naujienų.',
        ],
        'users' => [
            'delete' => 'Pareigos ir istorija išsaugomos — asmuo tiesiog nebematomas sąrašuose.',
        ],
        'institutions' => [
            'delete' => 'Institucijos pareigybės, posėdžiai ir kontaktai išsaugomi.',
        ],
        'forms' => [
            'delete' => 'Pateiktos registracijos išsaugomos.',
        ],
        'news' => [
            'delete' => 'Naujiena iškart nustos būti vieša, o kalbos pora atsilaisvins, kad kita kalba veiktų toliau.',
            'force_delete' => 'Nuoroda atsilaisvins ir ją bus galima panaudoti iš naujo.',
        ],
        'pages' => [
            'delete' => 'Puslapis iškart nustos būti viešas, o kalbos pora atsilaisvins, kad kita kalba veiktų toliau.',
            'force_delete' => 'Nuoroda atsilaisvins ir ją bus galima panaudoti iš naujo.',
        ],
        'navigation' => [
            'delete' => 'Vidiniai punktai ištrinami kartu ir atkuriami kartu.',
        ],
    ],

    'type_to_confirm' => 'Patvirtinimui įveskite šį tekstą:',
    'confirmation_label' => 'Patvirtinimo tekstas',
    'confirmation_placeholder' => 'Įveskite nurodytą tekstą',
];
