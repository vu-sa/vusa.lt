<?php

return [
    // General tour UI
    'next' => 'Kitas',
    'previous' => 'Atgal',
    'done' => 'Baigti',
    'skip' => 'Praleisti',
    'step_of' => '{{current}} iš {{total}}',
    
    // Atstovavimas Overview Tour
    'atstovavimas_overview' => [
        'welcome' => [
            'title' => 'Sveiki atvykę į Atstovavimą!',
            'description' => 'Ši sritis padės jums <strong>stebėti ir valdyti</strong> savo atstovavimo veiklą institucijose. Leiskite trumpai supažindinti su pagrindinėmis funkcijomis.',
        ],
        'institutions_card' => [
            'title' => 'Jūsų institucijos',
            'description' => 'Čia matote visas institucijas, kuriose atstovaujate. Kortelė rodo, kurioms institucijoms <strong>reikia dėmesio</strong> – ar trūksta susitikimų, ar artėja laikas planuoti naują veiklą.',
        ],
        'institution_item' => [
            'title' => 'Institucijos eilutė',
            'description' => 'Kiekviena institucija turi <strong>būsenos indikatorių</strong> ir veiksmo mygtukus. Galite suplanuoti susitikimą arba pranešti apie nebuvimą (atostogos, sesija ir pan.).',
        ],
        'meetings_card' => [
            'title' => 'Artėjantys susitikimai',
            'description' => 'Čia matote <strong>artimiausius suplanuotus susitikimus</strong>. Skaičius viršuje rodo, kiek susitikimų laukia. Spustelėkite ant susitikimo, kad peržiūrėtumėte detales.',
        ],
        'create_meeting' => [
            'title' => 'Naujo susitikimo kūrimas',
            'description' => 'Naudokite šį mygtuką, kad <strong>sukurtumėte naują susitikimą</strong>. Galite pasirinkti instituciją ir nustatyti datą bei darbotvarkę.',
        ],
        'all_meetings' => [
            'title' => 'Visų susitikimų peržiūra',
            'description' => 'Šis mygtukas atidaro <strong>pilną susitikimų sąrašą</strong> su išplėstine paieška ir filtrais. Čia rasite ir praėjusius susitikimus.',
        ],
        'timeline' => [
            'title' => 'Laiko juosta',
            'description' => 'Čia matote savo institucijų <strong>veiklos laiko juostą</strong> – susitikimus, spragas ir aktyvumo periodus. Tai padeda vizualiai planuoti atstovavimo veiklą.',
        ],
        'complete' => [
            'title' => 'Paruošta!',
            'description' => 'Dabar žinote pagrindines Atstovavimo funkcijas. Jei turite klausimų, kreipkitės į savo padalinio koordinatorių.',
        ],
    ],

    // Tenant Tab Spotlight
    'tenant_tab_spotlight' => [
        'title' => 'Padalinio vaizdas',
        'description' => 'Kaip vadovas, galite peržiūrėti viso padalinio institucijų laiko juostą. Spustelėkite čia, kad matytumėte bendrą vaizdą.',
    ],

    // Gantt Chart Tour
    'gantt_tour' => [
        'fullscreen' => [
            'title' => 'Viso ekrano režimas',
            'description' => 'Rekomenduojame pradėti nuo <strong>viso ekrano režimo</strong> – taip matysite daugiau informacijos ir bus patogiau naršyti.',
        ],
        'chart_overview' => [
            'title' => 'Laiko juostos diagrama',
            'description' => 'Tai yra <strong>Ganto diagrama</strong>, rodanti visų institucijų susitikimų istoriją ir būsimus susitikimus laiko ašyje. Kiekviena eilutė atspindi vieną instituciją.',
        ],
        'date_navigation' => [
            'title' => 'Metų navigacija',
            'description' => 'Spustelėkite datą, kad <strong>peršoktumėte į kitus metus</strong>. Taip pat rasite mygtuką grįžti į šiandieną.',
        ],
        'scale' => [
            'title' => 'Mastelio valdymas',
            'description' => 'Slankikliu galite <strong>keisti mastelio dydį</strong> – sumažinti, kad matytumėte daugiau laiko, arba padidinti detalesniam vaizdui.',
        ],
        'filters' => [
            'title' => 'Filtravimo parinktys',
            'description' => 'Spustelėkite šį mygtuką, kad <strong>atidarytumėte filtrus</strong>. Galite pasirinkti padalinius, rodyti tik aktyvias institucijas ar viešas institucijas.',
        ],
        'institution_row' => [
            'title' => 'Institucijos pavadinimas',
            'description' => 'Institucijos pavadinimas yra <strong>nuoroda</strong> – spustelėkite, kad atidarytumėte institucijos puslapį su visa informacija.',
        ],
        'meeting_icons' => [
            'title' => 'Susitikimų žymėjimai',
            'description' => 'Taškai diagramoje žymi <strong>susitikimus</strong>. Spustelėkite ant bet kurio taško, kad atidarytumėte susitikimo detales.',
        ],
        'safety_bands' => [
            'title' => 'Periodiškumo zonos',
            'description' => 'Žalios juostos rodo <strong>rekomenduojamą susitikimų dažnumą</strong>. Jei susitikimas vyksta zonoje – viskas gerai. Oranžinės linijos rodo, kad susitikimų trūksta.',
        ],
        'legend' => [
            'title' => 'Legenda',
            'description' => 'Baigėme! Spustelėkite čia, kad <strong>atidarytumėte legendą</strong> su visais diagramos elementų paaiškinimais.',
        ],
    ],

    // Admin Home Tour
    'admin_home' => [
        'welcome' => [
            'title' => 'Sveiki atvykę į Mano VU SA!',
            'description' => 'Tai yra jūsų <strong>pagrindinis darbalaukis</strong>, kuriame rasite svarbiausius įrankius ir informaciją. Leiskite trumpai supažindinti su pagrindinėmis funkcijomis.',
        ],
        'meetings_card' => [
            'title' => 'Artėjantys susitikimai',
            'description' => 'Ši kortelė rodo jūsų <strong>artimiausius suplanuotus susitikimus</strong>. Galite greitai sukurti naują susitikimą arba peržiūrėti visus.',
        ],
        'nav_visak' => [
            'title' => 'ViSAK – Atstovavimas',
            'description' => 'Šis mygtukas atidaro <strong>ViSAK</strong> – Virtualaus Studentų Atstovų Koordinatoriaus puslapį, kuriame galite valdyti visą savo atstovavimo veiklą.',
        ],
        'nav_administravimas' => [
            'title' => 'Administravimas',
            'description' => 'Šis mygtukas atidaro <strong>administravimo skyrių</strong>, kuriame galite valdyti vartotojus, padalinius, institucijas ir kitus nustatymus.',
        ],
        'quick_actions' => [
            'title' => 'Greiti veiksmai',
            'description' => 'Šioje sekcijoje rasite <strong>greitai prieinamus veiksmus</strong> – sukurti susitikimą, naujieną ar rezervaciją vienu paspaudimu.',
        ],
        'tasks_card' => [
            'title' => 'Užduotys',
            'description' => 'Ši kortelė rodo jūsų <strong>asmenines užduotis</strong> ir jų būseną. Matysite, kiek užduočių laukia ir kurios artėja.',
        ],
        'tasks_indicator' => [
            'title' => 'Užduočių indikatorius',
            'description' => 'Šis mygtukas rodo jūsų <strong>laukiančių užduočių skaičių</strong>. Spustelėkite, kad peržiūrėtumėte užduotis ar pažymėtumėte jas kaip atliktas.',
        ],
        'notifications_indicator' => [
            'title' => 'Pranešimai',
            'description' => 'Šis mygtukas rodo jūsų <strong>neperskaitytus pranešimus</strong>. Spustelėkite, kad peržiūrėtumėte naujausius pranešimus.',
        ],
        'help_button' => [
            'title' => 'Pagalbos mygtukas',
            'description' => 'Šis mygtukas leidžia <strong>paleisti šį vadovą iš naujo</strong> bet kada. Kiekvienas puslapis su vadovu turi šį mygtuką.',
        ],
        'nav_dokumentacija' => [
            'title' => 'Dokumentacija',
            'description' => 'Spustelėkite čia, kad atidarytumėte <strong>išsamią platformos dokumentaciją</strong> su visais paaiškinimais ir instrukcijomis.',
        ],
        'user_menu' => [
            'title' => 'Jūsų profilis',
            'description' => 'Šiame meniu rasite <strong>profilio nustatymus</strong>, kalbos ir temos parinktis bei atsijungimo mygtuką.',
        ],
        'nav_feedback' => [
            'title' => 'Palik atsiliepimą',
            'description' => 'Turi pastebėjimų ar pasiūlymų? <strong>Parašyk mums!</strong> Nuolat tobuliname platformą pagal jūsų atsiliepimus. Sėkmės! ✨',
        ],
    ],
];
