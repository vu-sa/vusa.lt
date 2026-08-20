<?php

/*
|--------------------------------------------------------------------------
| VU SA organisation details
|--------------------------------------------------------------------------
|
| Facts about the organisation itself, as opposed to per-tenant content. These
| were previously inline literals scattered across controllers, Blade mail
| templates and Vue components — the social links in particular had drifted into
| four mutually inconsistent sets (schema.org sameAs, the public header buttons
| and the summer-camps page all pointed at different accounts).
|
| Anything here changes rarely and needs a deploy. Values the board should be
| able to change on their own belong in app/Settings instead (see SiteSettings).
|
*/

return [

    /*
    |--------------------------------------------------------------------------
    | Contact addresses
    |--------------------------------------------------------------------------
    */

    'contacts' => [
        // Feedback forms and error pages point here.
        'it' => env('VUSA_IT_EMAIL', 'it@vusa.lt'),
        // Invoicing enquiries, linked from the public footer.
        'accounting' => env('VUSA_ACCOUNTING_EMAIL', 'saskaitos@vusa.lt'),
        'phone' => '+37052687144',
    ],

    /*
    |--------------------------------------------------------------------------
    | Social profiles
    |--------------------------------------------------------------------------
    |
    | Used both for the public navigation buttons and for the schema.org
    | Organization `sameAs` list, so they cannot disagree.
    |
    | NOTE: before centralising, four different sets of these were in use — the public
    | header buttons, the schema.org sameAs list and the summer-camps page all pointed at
    | different accounts. The header-button URLs won, on the grounds that they are the ones
    | visitors actually click. Worth a second pair of eyes: if any of these is the stale
    | one, this is now the single place to correct it.
    |
    */

    'social' => [
        'facebook' => 'https://www.facebook.com/vieningai.vu.sa',
        'instagram' => 'https://www.instagram.com/vu.studentu.atstovybe',
        'linkedin' => 'https://www.linkedin.com/company/vusa-lt',
    ],

    /*
    |--------------------------------------------------------------------------
    | Legal / registry details
    |--------------------------------------------------------------------------
    |
    | Shown in the public footer. Kept out of the translation files: these are
    | identifiers, not copy, and translating them makes no sense.
    |
    */

    'legal' => [
        'company_code' => '193077294',
        'vat_code' => 'LT100015645710',
        'address' => [
            'street' => 'Universiteto g. 3, Observatorijos kiemelis',
            'city' => '01513, Vilnius, Lietuva',
        ],
    ],

];
