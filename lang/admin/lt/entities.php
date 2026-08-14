<?php

return [
    'document' => [
        'model' => '{1} dokumentas|[2,9] dokumentai|[10,*] dokumentų',
        'title' => 'pavadinimas',
        'sharepoint_id' => 'SharePoint ID',
        'eTag' => 'eTag',
        'document_date' => 'dokumento data',
        'language' => 'kalba',
        'content_type' => 'turinio tipas',
        'institution_id' => 'institucija',
        'public_url' => 'viešas URL',
        'public_url_created_at' => 'viešo URL sukūrimo data',
        'thumbnail_url' => 'miniatiūros URL',
        'is_active' => 'aktyvus?',
        'sharepoint_site_id' => 'SharePoint svetainės ID',
        'sharepoint_list_id' => 'SharePoint sąrašo ID',
    ],
    'duty' => [
        'model' => '{1} pareiga|[2,9] pareigos|[10,*] pareigų',
    ],
    'user' => [
        'model' => '{1} narys|[2,9] nariai|[10,*] narių',
    ],
    'news' => [
        'model' => '{1} naujiena|[2,9] naujienos|[10,*] naujienų',
    ],
    'page' => [
        'model' => '{1} puslapis|[2,9] puslapiai|[10,*] puslapių',
    ],
    'banner' => [
        'model' => '{1} baneris|[2,9] baneriai|[10,*] banerių',
    ],
    'category' => [
        'model' => '{1} kategorija|[2,9] kategorijos|[10,*] kategorijų',
    ],
    'tag' => [
        'model' => '{1} žyma|[2,9] žymos|[10,*] žymų',
    ],
    'type' => [
        'model' => '{1} turinio tipas|[2,9] turinio tipai|[10,*] turinio tipų',
    ],
    'relationship' => [
        'model' => '{1} ryšys|[2,9] ryšiai|[10,*] ryšių',
    ],
    'calendar' => [
        'model' => '{1} renginys|[2,9] renginiai|[10,*] renginių',
    ],
    'form' => [
        'model' => '{1} forma|[2,9] formos|[10,*] formų',
    ],
    'role' => [
        'model' => '{1} rolė|[2,9] rolės|[10,*] rolių',
    ],
    'permission' => [
        'model' => '{1} teisė|[2,9] teisės|[10,*] teisių',
    ],
    'studyProgram' => [
        'model' => '{1} studijų programa|[2,9] studijų programos|[10,*] studijų programų',
    ],
    'studySet' => [
        'model' => '{1} individualių studijų komplektas|[2,9] individualių studijų komplektai|[10,*] individualių studijų komplektų',
    ],
    'institution' => [
        'model' => '{1} institucija|[2,9] institucijos|[10,*] institucijų',
        'name' => 'pavadinimas',
        'description' => 'aprašymas',
        'is_active' => 'aktyvi?',
        'is_default' => 'numatytoji?',
        'is_public' => 'vieša?',
        'is_visible' => 'matoma?',
    ],
    'meeting' => [
        'model' => '{1} susitikimas|[2,9] susitikimai|[10,*] susitikimų',
    ],
    'tenant' => [
        'model' => '{1} padalinys|[2,9] padaliniai|[10,*] padalinių',
    ],
    'reservation' => [
        'model' => '{1} rezervacija|[2,9] rezervacijos|[10,*] rezervacijų',
        'managers' => '{1} rezervacijos valdytojas|[2,9] rezervacijos valdytojai|[10,*] rezervacijos valdytojų',
        'start_time' => 'rez. pradžia',
        'end_time' => 'rez. pabaiga',
        'resources' => 'rezervuoti ištekliai',
        'is_reservable' => 'ar rezervuojamas?',
        'period' => 'rezervacijos laikotarpis',
    ],
    'resource' => [
        'model' => '{1} išteklius|[2,9] ištekliai|[10,*] išteklių',
    ],
    'resource_category' => [
        'model' => '{1} išteklių kategorija|[2,9] išteklių kategorijos|[10,*] išteklių kategorijų',
    ],
    'reservation_resource' => [
        'model' => '{1} rezervacijos išteklius|[2,9] rezervacijos ištekliai|[10,*] rezervacijos išteklių',
    ],
    'meta' => [
        'model_list' => ':model sąrašas',
        'help' => 'Kaip veikia :model?',
    ],
    'problem' => [
        'model' => '{1} problema|[2,9] problemos|[10,*] problemų',
        'title' => 'problemos pavadinimas',
        'description' => 'problemos aprašymas',
        'solution' => 'sprendimas',
        'steps_taken' => 'Atlikti žingsniai',
        'occurred_at' => 'įvykimo data',
        'resolved_at' => 'išsprendimo data',
        'status' => 'būsena',
        'responsible_user' => 'atsakingas asmuo',
        'categories' => 'kategorijos',
        'status_options' => [
            'open' => 'Atvira',
            'in_progress' => 'Vykdoma',
            'resolved' => 'Išspręsta',
        ],
    ],

    'contentPart' => [
        'content_summary' => 'turinys',
        'type' => 'bloko tipas',
        'options' => 'nustatymai',
    ],

    // Fallback field labels shared across the activity log for any model
    // without a more specific entry above -- see App\Services\ActivityChangeFormatter.
    'common' => [
        'name' => 'pavadinimas',
        'title' => 'pavadinimas',
        'short_name' => 'trumpas pavadinimas',
        'description' => 'aprašymas',
        'order' => 'eiliškumas',
        'is_active' => 'aktyvus?',
        'start_time' => 'pradžia',
        'end_time' => 'pabaiga',
        'address' => 'adresas',
        'email' => 'el. paštas',
        'phone' => 'telefonas',
        'url' => 'nuoroda',
        'image_url' => 'paveikslėlio nuoroda',
        'link_url' => 'nuoroda',
        'lang' => 'kalba',
        'status' => 'būsena',
        'note' => 'pastaba',
        'notes_html' => 'pastabos',
        'permalink' => 'nuolatinė nuoroda',
        'publish_time' => 'publikavimo laikas',
        'main_image' => 'pagrindinis paveikslėlis',
        'location' => 'vieta',
        'organizer' => 'organizatorius',
        'video_url' => 'vaizdo įrašo nuoroda',
        'facebook_url' => 'Facebook nuoroda',
        'max_participants' => 'dalyvių limitas',
        'tenant' => 'padalinys',
        'institution' => 'institucija',
        'category' => 'kategorija',
        'meeting' => 'susitikimas',
        'agenda_item' => 'darbotvarkės klausimas',
        // Relation names, for relation_updated activities (see
        // App\Support\AuditedRelations / LogsRelationshipChanges).
        'users' => 'nariai',
        'types' => 'tipai',
        'institutions' => 'institucijos',
        'resources' => 'ištekliai',
    ],
];
