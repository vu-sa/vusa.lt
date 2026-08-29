<?php

/*
 * Entity names used across the admin UI (table empty states, breadcrumbs, flash messages).
 *
 * Every entity declares:
 *  - 'model'  — a pluralization string with the {1} / [2,9] / [10,*] forms Lithuanian needs.
 *  - 'gender' — 'f' (moteriškoji giminė) or 'm' (vyriškoji giminė) of the noun in 'model'.
 *    Lithuanian participles agree with their subject, so messages.php keeps one variant per
 *    gender and App\Http\Controllers\AdminController::entityMessage() picks between them.
 *
 * Keys are camelCase, matching the `entityName` constants declared by admin index pages.
 */

return [
    'document' => [
        'model' => '{1} dokumentas|[2,9] dokumentai|[10,*] dokumentų',
        'gender' => 'm',
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
        'gender' => 'f',
    ],
    'dutiable' => [
        'model' => '{1} pareigybės laikotarpis|[2,9] pareigybės laikotarpiai|[10,*] pareigybės laikotarpių',
        'gender' => 'm',
    ],
    'cadence' => [
        'model' => '{1} kadencija|[2,9] kadencijos|[10,*] kadencijų',
        'gender' => 'f',
    ],
    'user' => [
        'model' => '{1} narys|[2,9] nariai|[10,*] narių',
        'gender' => 'm',
    ],
    'news' => [
        'model' => '{1} naujiena|[2,9] naujienos|[10,*] naujienų',
        'gender' => 'f',
    ],
    'page' => [
        'model' => '{1} puslapis|[2,9] puslapiai|[10,*] puslapių',
        'gender' => 'm',
    ],
    'banner' => [
        'model' => '{1} baneris|[2,9] baneriai|[10,*] banerių',
        'gender' => 'm',
    ],
    'category' => [
        'model' => '{1} kategorija|[2,9] kategorijos|[10,*] kategorijų',
        'gender' => 'f',
    ],
    'tag' => [
        'model' => '{1} žyma|[2,9] žymos|[10,*] žymų',
        'gender' => 'f',
    ],
    'type' => [
        'model' => '{1} turinio tipas|[2,9] turinio tipai|[10,*] turinio tipų',
        'gender' => 'm',
    ],
    'relationship' => [
        'model' => '{1} ryšys|[2,9] ryšiai|[10,*] ryšių',
        'gender' => 'm',
    ],
    'relationshipType' => [
        'model' => '{1} ryšio tipas|[2,9] ryšio tipai|[10,*] ryšio tipų',
        'gender' => 'm',
    ],
    'calendar' => [
        'model' => '{1} renginys|[2,9] renginiai|[10,*] renginių',
        'gender' => 'm',
    ],
    'form' => [
        'model' => '{1} forma|[2,9] formos|[10,*] formų',
        'gender' => 'f',
    ],
    'role' => [
        'model' => '{1} rolė|[2,9] rolės|[10,*] rolių',
        'gender' => 'f',
    ],
    'permission' => [
        'model' => '{1} teisė|[2,9] teisės|[10,*] teisių',
        'gender' => 'f',
    ],
    'studyProgram' => [
        'model' => '{1} studijų programa|[2,9] studijų programos|[10,*] studijų programų',
        'gender' => 'f',
    ],
    'studySet' => [
        'model' => '{1} individualių studijų komplektas|[2,9] individualių studijų komplektai|[10,*] individualių studijų komplektų',
        'gender' => 'm',
    ],
    'institution' => [
        'model' => '{1} institucija|[2,9] institucijos|[10,*] institucijų',
        'gender' => 'f',
        'name' => 'pavadinimas',
        'description' => 'aprašymas',
        'is_active' => 'aktyvi?',
        'is_default' => 'numatytoji?',
        'is_public' => 'vieša?',
        'is_visible' => 'matoma?',
    ],
    'meeting' => [
        'model' => '{1} susitikimas|[2,9] susitikimai|[10,*] susitikimų',
        'gender' => 'm',
    ],
    'agendaItem' => [
        'model' => '{1} darbotvarkės punktas|[2,9] darbotvarkės punktai|[10,*] darbotvarkės punktų',
        'gender' => 'm',
    ],
    'vote' => [
        'model' => '{1} balsavimas|[2,9] balsavimai|[10,*] balsavimų',
        'gender' => 'm',
    ],
    'tenant' => [
        'model' => '{1} padalinys|[2,9] padaliniai|[10,*] padalinių',
        'gender' => 'm',
    ],
    'reservation' => [
        'model' => '{1} rezervacija|[2,9] rezervacijos|[10,*] rezervacijų',
        'gender' => 'f',
        'managers' => '{1} rezervacijos valdytojas|[2,9] rezervacijos valdytojai|[10,*] rezervacijos valdytojų',
        'start_time' => 'rez. pradžia',
        'end_time' => 'rez. pabaiga',
        'resources' => 'rezervuoti ištekliai',
        'is_reservable' => 'ar rezervuojamas?',
        'period' => 'rezervacijos laikotarpis',
    ],
    'resource' => [
        'model' => '{1} išteklius|[2,9] ištekliai|[10,*] išteklių',
        'gender' => 'm',
    ],
    'resourceCategory' => [
        'model' => '{1} išteklių kategorija|[2,9] išteklių kategorijos|[10,*] išteklių kategorijų',
        'gender' => 'f',
    ],
    'reservationResource' => [
        'model' => '{1} rezervacijos išteklius|[2,9] rezervacijos ištekliai|[10,*] rezervacijos išteklių',
        'gender' => 'm',
    ],
    'comment' => [
        'model' => '{1} komentaras|[2,9] komentarai|[10,*] komentarų',
        'gender' => 'm',
    ],
    'task' => [
        'model' => '{1} užduotis|[2,9] užduotys|[10,*] užduočių',
        'gender' => 'f',
    ],
    'quickLink' => [
        'model' => '{1} greitoji nuoroda|[2,9] greitosios nuorodos|[10,*] greitųjų nuorodų',
        'gender' => 'f',
    ],
    'navigation' => [
        'model' => '{1} navigacijos elementas|[2,9] navigacijos elementai|[10,*] navigacijos elementų',
        'gender' => 'm',
    ],
    'file' => [
        'model' => '{1} failas|[2,9] failai|[10,*] failų',
        'gender' => 'm',
    ],
    'folder' => [
        'model' => '{1} aplankas|[2,9] aplankai|[10,*] aplankų',
        'gender' => 'm',
    ],
    'meta' => [
        'model_list' => ':model sąrašas',
        'help' => 'Kaip veikia :model?',
    ],
    'problem' => [
        'model' => '{1} problema|[2,9] problemos|[10,*] problemų',
        'gender' => 'f',
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
