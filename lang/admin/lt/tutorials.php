<?php

return [
    // General tour UI
    'next' => 'Kitas',
    'previous' => 'Atgal',
    'done' => 'Baigti',
    'skip' => 'Praleisti',
    'step_of' => '{{current}} iš {{total}}',

    // ViSAK overview (/mano/dashboard/atstovavimas)
    'atstovavimas_overview' => [
        'welcome' => [
            'title' => 'ViSAK apžvalga',
            'description' => 'Čia matai savo atstovavimo darbą institucijose: kur <strong>reikia dėmesio</strong>, kas artėja ir kaip sekėsi iki šiol.',
        ],
        'institutions_card' => [
            'title' => 'Kur reikia dėmesio',
            'description' => 'Institucijos, kuriose seniai užfiksuotas posėdis ar veikla. Iš čia iškart <strong>užfiksuosi posėdį</strong> arba pažymėsi, kad jo nebuvo (atostogos, sesija ir pan.).',
        ],
        'meetings_card' => [
            'title' => 'Artėjantys posėdžiai',
            'description' => 'Artimiausi suplanuoti posėdžiai. Paspausk ant posėdžio, kad pamatytum darbotvarkę ir detales.',
        ],
        'create_meeting' => [
            'title' => 'Naujas posėdis',
            'description' => 'Per <strong>+ Sukurti</strong> užregistruosi naują posėdį: pasirinksi instituciją, datą ir darbotvarkę.',
        ],
        'timeline' => [
            'title' => 'Laiko juosta',
            'description' => 'Tavo institucijų <strong>veiklos laiko juosta</strong> – posėdžiai, spragos ir aktyvumo periodai. Padeda planuoti į priekį.',
        ],
        'complete' => [
            'title' => 'Viskas!',
            'description' => 'Turi klausimų? Kreipkis į savo padalinio koordinatorių. Šį turą bet kada pakartosi per <strong>Pagalba</strong>.',
        ],
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

    // Admin Home / Welcome Tour (≤ 5 steps)
    'admin_home' => [
        'welcome' => [
            'title' => 'Labas! Čia Mano VU SA',
            'description' => 'Trumpai parodysime, kur kas yra: kaip judėti tarp sričių, rasti ir sukurti tai, ko reikia, ir kur tavęs laukia užduotys. Užtruks mažiau nei minutę.',
        ],
        'all_sections' => [
            'title' => 'Visi skyriai',
            'description' => 'Vienoje vietoje – viskas, ką gali atidaryti su savo paskyra. Pravers, kai nežinai, kurioje srityje ko ieškoti.',
        ],
        'quick_actions' => [
            'title' => 'Greiti veiksmai',
            'description' => 'Kūrimo veiksmai iš sričių, kuriose gali dirbti – tie patys kaip <strong>+ Sukurti</strong>, tik be papildomo paspaudimo.',
        ],
        'section_switcher' => [
            'title' => 'Kur esi',
            'description' => 'Čia matai, kurioje srityje ir skyriuje esi. Paspausk, kad greitai peršoktum į kitą skyrių.',
        ],
        'command_palette_mobile' => [
            'title' => 'Paieška',
            'description' => 'Rask puslapius, dokumentus, kontaktus ar neseniai redaguotus įrašus.',
        ],
        'mobile_menu' => [
            'title' => 'Meniu',
            'description' => 'Kitos darbo sritys, paskyra, nustatymai ir pagalba – čia pat galėsi pakartoti ir šį turą.',
        ],
        'workspaces' => [
            'title' => 'Darbo sritys',
            'description' => 'Mano VU SA suskirstyta į sritis pagal tavo pareigybes ir teises (Pradžia, ViSAK, Rezervacijos ir kt.). Čia greitai persijungsi tarp jų.',
        ],
        'command_palette' => [
            'title' => 'Greitoji paieška',
            'description' => 'Paspausk <strong>Ctrl+K</strong> (arba <strong>⌘K</strong>) arba paieškos laukelį – rasi puslapius, dokumentus, kontaktus ar neseniai redaguotus įrašus.',
        ],
        'action_create' => [
            'title' => 'Greitas kūrimas',
            'description' => 'Vienas mygtukas visiems veiksmams – pradėk naują posėdį, pateik rezervaciją ar sukurk registraciją.',
        ],
        'tasks_card' => [
            'title' => 'Užduotys ir dėmesio eilė',
            'description' => 'Tavo asmeninės užduotys ir artėjantys priminimai visada matomi Pradžioje – niekas nepasimes.',
        ],
        'account_menu' => [
            'title' => 'Paskyra ir pagalba',
            'description' => 'Čia rasi savo profilį, temos bei kalbos nustatymus, dokumentaciją ir pagalbos meniu.',
        ],
    ],

    // Dutiable timeline editor (/mano/dutiables/timeline)
    'dutiable_timeline' => [
        'welcome' => [
            'title' => 'Pareigybių laikotarpiai',
            'description' => 'Čia vienoje juostoje matote <strong>visus institucijos pareigybių laikotarpius</strong>. Vietoj to, kad kiekvieną narį redaguotumėte atskirai, viską galite sutvarkyti iš karto.',
        ],
        'institution' => [
            'title' => 'Institucija',
            'description' => 'Rodoma institucija. Spustelėję pasirinksite kitą – pirmiausia siūlomos tos, kuriose einate pareigas.',
        ],
        'chart' => [
            'title' => 'Laiko juosta',
            'description' => 'Kiekviena juostelė – vienas pareigybės laikotarpis. Žalias fonas rodo <strong>kadenciją</strong>: juostelė, kuri nesutampa su jos kraštu, ir yra tai, ką čia taisome. Juostelę galima tempti; kaip – parašyta prie „i“ ženklo dešinėje.',
        ],
        'controls' => [
            'title' => 'Suskleidimas ir rikiavimas',
            'description' => 'Suskleiskite visas pareigybes, kad matytumėte bendrą vaizdą. Kur nurodytos studijų programos, įrašus galima surikiuoti pagal jas.',
        ],
        'filters' => [
            'title' => 'Filtrai',
            'description' => 'Filtruokite pagal kadenciją ar padalinį. Ten pat pasirinksite, ar rodyti <strong>pasibaigusius</strong> laikotarpius.',
        ],
        'selection' => [
            'title' => 'Pažymėtas įrašas',
            'description' => 'Pažymėjus juostelę čia matysite tikslias datas ir veiksmus: sulygiuoti su kadencija, užbaigti, sujungti ar pašalinti.',
        ],
        'suggestions' => [
            'title' => 'Siūlomi taisymai',
            'description' => 'Sistema pati randa neatitikimus – persidengiančius laikotarpius, neterminuotas pareigas po pasibaigusios kadencijos. Galite pritaikyti juos vienu paspaudimu.',
        ],
        'save' => [
            'title' => 'Peržiūra ir išsaugojimas',
            'description' => 'Niekas neišsaugoma, kol nepaspaudžiate. Prieš tai galite <strong>peržiūrėti</strong>, kaip įrašai atrodys po pakeitimų.',
        ],
    ],
];
