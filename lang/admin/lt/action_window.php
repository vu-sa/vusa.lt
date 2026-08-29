<?php

return [
    'trigger' => 'Greiti veiksmai',
    'trigger_short' => 'Veiksmai',
    'spotlight' => [
        'title' => 'Nežinai, nuo ko pradėti?',
        'description' => 'Paspaudęs čia rasi dažniausius veiksmus — pranešti apie posėdį, pažymėti, kad posėdžių nebus, ar papildyti jau įvykusį posėdį.',
    ],
    'personas' => [
        'title' => 'Ką norėtum padaryti?',
        'representative' => [
            'title' => 'Kaip studentų atstovas',
            'description' => 'Posėdžiai, darbotvarkės, problemos',
        ],
        'member' => [
            'title' => 'Kaip VU SA narys',
            'description' => 'Rezervacijos ir problemos',
        ],
        'coordinator' => [
            'title' => 'Kaip koordinatorius',
            'description' => 'Pareigybės ir kadencijos',
        ],
    ],
    'actions' => [
        'new_meeting' => [
            'title' => 'Pranešti apie posėdį',
            'description' => 'Įvyks arba jau įvyko posėdis — užregistruokime jį.',
        ],
        'no_meeting' => [
            'title' => 'Posėdžio kurį laiką nebus',
            'description' => 'Pvz. sesija ar atostogos — kad negautum nereikalingų priminimų.',
        ],
        'complete_meeting' => [
            'title' => 'Papildyti posėdį',
            'description' => 'Pridėti darbotvarkę ar sprendimus prie jau įvykusio posėdžio.',
        ],
        'new_problem' => [
            'title' => 'Pranešti apie problemą',
            'description' => 'Pastebėjai, kas studentams neveikia — papasakok.',
        ],
        'new_reservation' => [
            'title' => 'Rezervuoti inventorių',
            'description' => 'Pasiimti VU SA daiktą ar patalpą.',
        ],
        'duty_update' => [
            'title' => 'Atnaujinti pareigybes',
            'description' => 'Priskirti žmones prie pareigybių naujai kadencijai.',
        ],
        'cadences' => [
            'title' => 'Tvarkyti kadencijas',
            'description' => 'Nustatyti, kada prasideda ir baigiasi kadencijos.',
        ],
    ],
    'institution' => [
        'title' => 'Kuriai institucijai?',
        'subtitle' => 'Rodomos institucijos, kuriose eini pareigas.',
        'search' => 'Ieškoti institucijos',
        'empty' => 'Neturi institucijų, kurioms galėtum tai padaryti.',
        'no_meetings_yet' => 'Posėdžių dar nebuvo',
        'last_meeting' => 'Paskutinis posėdis prieš :days d.',
        'check_in_until' => 'Pažymėta, kad posėdžio nebus iki :date',
    ],
    'meeting' => [
        'type' => [
            'title' => 'Kaip vyks posėdis?',
            'subtitle' => 'Nuo to priklauso, ar reikės nurodyti tikslų laiką.',
            'email' => 'Balsuojama el. paštu — pakanka datos.',
            'other' => 'Nė vienas variantas netinka.',
        ],
        'when' => [
            'title' => 'Kada vyks posėdis?',
            'subtitle' => 'Siūlome pagal tai, kada ši institucija posėdžiaudavo iki šiol.',
            'usual_hint' => 'Artimiausias įprastas laikas',
            'week_after_hint' => 'Savaite vėliau',
            'custom' => 'Pasirinkti kitą datą…',
        ],
        'date' => [
            'title' => 'Kurią dieną?',
            'subtitle' => 'Gali pasirinkti ir praėjusią dieną, jei posėdis jau įvyko.',
        ],
        'time' => [
            'title' => 'Kelintą valandą?',
            'usual' => 'Įprastas šios institucijos laikas',
            'custom' => 'Nurodyti tikslų laiką…',
        ],
        'agenda' => [
            'title' => 'Ką svarstysite?',
            'subtitle' => 'Darbotvarkę galėsi papildyti ir vėliau.',
            'add' => 'Surašyti klausimus',
            'add_description' => 'Įrašyk, kokie klausimai bus svarstomi.',
            'placeholder' => 'Klausimo pavadinimas',
            'add_another' => 'Pridėti klausimą',
            'remove' => 'Šalinti klausimą',
            'skip' => 'Praleisti',
            'skip_description' => 'Pridėsiu klausimus vėliau.',
            'back_to_choice' => 'Grįžti prie pasirinkimo',
        ],
        'review' => [
            'title' => 'Ar viskas gerai?',
            'subtitle' => 'Paspausk ant eilutės, jei nori ką nors pakeisti.',
            'institution' => 'Institucija',
            'type' => 'Tipas',
            'when' => 'Laikas',
            'agenda' => 'Darbotvarkė',
            'agenda_count' => '{0} Nėra klausimų|{1} :count klausimas|[2,9] :count klausimai|[10,*] :count klausimų',
            'submit' => 'Sukurti posėdį',
            'submitting' => 'Kuriama…',
        ],
    ],
    'check_in' => [
        'explainer_title' => 'Ką tai reiškia?',
        'explainer' => 'Jei žinai, kad nurodytu laikotarpiu posėdžių nebus (pvz. atostogos, egzaminų sesija), pranešimas padės išvengti nereikalingų priminimų.',
        'until' => [
            'title' => 'Iki kada posėdžių nebus?',
            'subtitle' => 'Pradedame nuo šiandien.',
            'two_weeks' => 'Dvi savaites',
            'month_end' => 'Iki mėnesio pabaigos',
            'three_months' => 'Tris mėnesius',
            'custom' => 'Pasirinkti datas…',
            'max_hint' => 'Ilgiausiai galima pranešti tris mėnesius į priekį.',
            'from' => 'Nuo',
            'to' => 'Iki',
        ],
        'review' => [
            'title' => 'Pranešti, kad posėdžių nebus',
            'subtitle' => 'Paspausk ant eilutės, jei nori ką nors pakeisti.',
            'period' => 'Laikotarpis',
            'note' => 'Pastaba (neprivaloma)',
            'note_placeholder' => 'Pvz. atostogų laikotarpis, neveiklus semestro metas…',
            'submit' => 'Pranešti',
            'submitting' => 'Siunčiama…',
        ],
    ],
    'meeting_picker' => [
        'title' => 'Kurį posėdį papildyti?',
        'subtitle' => 'Viršuje — tie, kuriems labiausiai trūksta informacijos.',
        'empty' => 'Visi tavo posėdžiai užpildyti. Puiku!',
        'missing_agenda' => 'Nėra darbotvarkės',
        'missing_decisions' => 'Trūksta sprendimų',
    ],
    'common' => [
        'back' => 'Atgal',
        'close' => 'Uždaryti',
        'change' => 'Keisti',
        'continue' => 'Toliau',
        'loading' => 'Kraunama…',
        'error' => 'Nepavyko įkelti duomenų. Bandyk dar kartą.',
    ],
];
