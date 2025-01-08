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
    'meeting' => [
        'model' => '{1} susitikimas|[2,9] susitikimai|[10,*] susitikimų',
    ],
    'training' => [
        'model' => '{1} mokymas|[2,9] mokymai|[10,*] mokymų',
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
        'model' => '{1} išteklių kategorija|[2,9] išteklių kategorijos|[10,19] išteklių kategorijų',
    ],
    'reservation_resource' => [
        'model' => '{1} rezervacijos išteklius|[2,9] rezervacijos ištekliai|[10,*] rezervacijos išteklių',
    ],
    'meta' => [
        'model_list' => ':model sąrašas',
        'help' => 'Kaip veikia :model?',
    ],
    'membership' => [
        'model' => '{1} narystė|[2,9] narystės|[10,*] narystės',
    ],
];
