<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted' => ':attribute turi būti patvirtintas (-a).',
    'accepted_if' => ':attribute turi būti patvirtintas (-a), kai :other yra :value.',
    'active_url' => ':attribute nėra teisingas URL adresas.',
    'after' => ':attribute turi būti data po :date.',
    'after_or_equal' => ':attribute turi būti data, ne ankstesnė nei :date.',
    'alpha' => ':attribute gali būti sudarytas (-a) tik iš raidžių.',
    'alpha_dash' => ':attribute gali būti sudarytas (-a) tik iš raidžių, skaičių, brūkšnelių ir pabraukimų.',
    'alpha_num' => ':attribute gali būti sudarytas (-a) tik iš raidžių ir skaičių.',
    'array' => ':attribute turi būti masyvas.',
    'ascii' => ':attribute gali būti sudarytas (-a) tik iš vienbaičių raidinių, skaitinių ir simbolinių ženklų.',

    'before' => ':attribute turi būti data prieš :date.',
    'before_or_equal' => ':attribute turi būti data, ne vėlesnė nei :date.',
    'between' => [
        'numeric' => ':attribute turi būti nuo :min iki :max.',
        'file' => ':attribute turi būti nuo :min iki :max kilobaitų.',
        'string' => ':attribute turi būti nuo :min iki :max simbolių.',
        'array' => ':attribute turi turėti nuo :min iki :max elementų.',
    ],
    'boolean' => ':attribute reikšmė turi būti „taip“ arba „ne“.',
    'confirmed' => ':attribute patvirtinimas nesutampa.',
    'current_password' => 'Neteisingas slaptažodis.',
    'date' => ':attribute nėra teisinga data.',
    'date_equals' => ':attribute turi būti data, lygi :date.',
    'date_format' => ':attribute neatitinka formato :format.',
    'decimal' => ':attribute turi turėti :decimal skaičius po kablelio.',
    'declined' => ':attribute turi būti atmestas (-a).',
    'declined_if' => ':attribute turi būti atmestas (-a), kai :other yra :value.',
    'different' => ':attribute ir :other turi skirtis.',
    'digits' => ':attribute turi būti sudarytas (-a) iš :digits skaitmenų.',
    'digits_between' => ':attribute turi būti sudarytas (-a) nuo :min iki :max skaitmenų.',
    'dimensions' => ':attribute paveikslėlio matmenys netinkami.',
    'distinct' => ':attribute reikšmė kartojasi.',
    'doesnt_end_with' => ':attribute negali baigtis viena iš šių reikšmių: :values.',
    'doesnt_start_with' => ':attribute negali prasidėti viena iš šių reikšmių: :values.',
    'email' => ':attribute pateiktas neteisingu formatu.',
    'ends_with' => ':attribute turi baigtis viena iš šių reikšmių: :values.',
    'enum' => 'Pasirinkta :attribute reikšmė yra netinkama.',
    'exists' => 'Pasirinkta :attribute reikšmė yra netinkama.',
    'file' => ':attribute turi būti failas.',
    'filled' => ':attribute laukelis yra privalomas.',
    'gt' => [
        'numeric' => ':attribute turi būti didesnis (-ė) nei :value.',
        'file' => ':attribute turi būti didesnis (-ė) nei :value kilobaitų.',
        'string' => ':attribute turi būti ilgesnis (-ė) nei :value simbolių.',
        'array' => ':attribute turi turėti daugiau nei :value elementų.',
    ],
    'gte' => [
        'numeric' => ':attribute turi būti ne mažesnis (-ė) nei :value.',
        'file' => ':attribute turi būti ne mažesnis (-ė) nei :value kilobaitų.',
        'string' => ':attribute turi būti ne trumpesnis (-ė) nei :value simbolių.',
        'array' => ':attribute turi turėti ne mažiau nei :value elementų.',
    ],
    'image' => ':attribute turi būti paveikslėlis.',
    'in' => 'Pasirinkta :attribute reikšmė yra netinkama.',
    'in_array' => ':attribute reikšmės nėra tarp :other reikšmių.',
    'integer' => ':attribute turi būti sveikasis skaičius.',
    'ip' => ':attribute turi būti teisingas IP adresas.',
    'ipv4' => ':attribute turi būti teisingas IPv4 adresas.',
    'ipv6' => ':attribute turi būti teisingas IPv6 adresas.',
    'json' => ':attribute turi būti teisinga JSON eilutė.',
    'lowercase' => ':attribute turi būti parašytas (-a) mažosiomis raidėmis.',
    'lt' => [
        'numeric' => ':attribute turi būti mažesnis (-ė) nei :value.',
        'file' => ':attribute turi būti mažesnis (-ė) nei :value kilobaitų.',
        'string' => ':attribute turi būti trumpesnis (-ė) nei :value simbolių.',
        'array' => ':attribute turi turėti mažiau nei :value elementų.',
    ],
    'lte' => [
        'numeric' => ':attribute turi būti ne didesnis (-ė) nei :value.',
        'file' => ':attribute turi būti ne didesnis (-ė) nei :value kilobaitų.',
        'string' => ':attribute turi būti ne ilgesnis (-ė) nei :value simbolių.',
        'array' => ':attribute turi turėti ne daugiau nei :value elementų.',
    ],
    'mac_address' => ':attribute turi būti teisingas MAC adresas.',
    'max' => [
        'numeric' => ':attribute negali būti didesnis (-ė) nei :max.',
        'file' => ':attribute negali būti didesnis (-ė) nei :max kilobaitų.',
        'string' => ':attribute negali būti ilgesnis (-ė) nei :max simbolių.',
        'array' => ':attribute negali turėti daugiau nei :max elementų.',
    ],
    'max_digits' => ':attribute negali turėti daugiau nei :max skaitmenų.',
    'mimes' => ':attribute turi būti šio tipo failas: :values.',
    'mimetypes' => ':attribute turi būti šio tipo failas: :values.',
    'min' => [
        'numeric' => ':attribute turi būti ne mažesnis (-ė) nei :min.',
        'file' => ':attribute turi būti ne mažesnis (-ė) nei :min kilobaitų.',
        'string' => ':attribute turi būti ne trumpesnis (-ė) nei :min simbolių.',
        'array' => ':attribute turi turėti ne mažiau nei :min elementų.',
    ],
    'min_digits' => ':attribute turi turėti ne mažiau nei :min skaitmenų.',
    'multiple_of' => ':attribute turi būti :value kartotinis.',
    'not_in' => 'Pasirinkta :attribute reikšmė yra netinkama.',
    'not_regex' => ':attribute formatas yra netinkamas.',
    'numeric' => ':attribute turi būti skaičius.',
    'password' => [
        'letters' => ':attribute turi turėti bent vieną raidę.',
        'mixed' => ':attribute turi turėti bent vieną didžiąją ir vieną mažąją raidę.',
        'numbers' => ':attribute turi turėti bent vieną skaičių.',
        'symbols' => ':attribute turi turėti bent vieną simbolį.',
        'uncompromised' => 'Šis :attribute buvo aptiktas duomenų nutekėjime. Pasirinkite kitą.',
    ],
    'present' => ':attribute laukelis turi būti pateiktas.',
    'prohibited' => ':attribute laukelis yra draudžiamas.',
    'prohibited_if' => ':attribute laukelis yra draudžiamas, kai :other yra :value.',
    'prohibited_unless' => ':attribute laukelis yra draudžiamas, nebent :other yra tarp :values.',
    'prohibits' => ':attribute laukelis neleidžia pateikti :other.',
    'regex' => ':attribute formatas yra netinkamas.',
    'required' => ':attribute yra privalomas (-a).',
    'required_array_keys' => ':attribute turi turėti įrašus: :values.',
    'required_if' => ':attribute laukelis yra privalomas, kai :other yra :value.',
    'required_if_accepted' => ':attribute laukelis yra privalomas, kai :other yra patvirtintas.',
    'required_unless' => ':attribute laukelis yra privalomas, nebent :other yra tarp :values.',
    'required_with' => ':attribute laukelis yra privalomas, kai pateiktas :values.',
    'required_with_all' => ':attribute laukelis yra privalomas, kai pateikti :values.',
    'required_without' => ':attribute laukelis yra privalomas, kai nepateiktas :values.',
    'required_without_all' => ':attribute laukelis yra privalomas, kai nepateiktas nė vienas iš :values.',
    'same' => ':attribute ir :other turi sutapti.',
    'size' => [
        'numeric' => ':attribute turi būti :size.',
        'file' => ':attribute turi būti :size kilobaitų.',
        'string' => ':attribute turi būti :size simbolių.',
        'array' => ':attribute turi turėti :size elementų.',
    ],
    'starts_with' => ':attribute turi prasidėti viena iš šių reikšmių: :values.',
    'string' => ':attribute turi būti tekstas.',
    'timezone' => ':attribute turi būti teisinga laiko juosta.',
    'unique' => ':attribute jau naudojama. Pasirinkite / įrašykite kitą reikšmę.',
    'unique_trashed' => ':attribute naudoja ištrintas įrašas. Susirask jį ištrintų įrašų rodinyje ir atkurk arba ištrink negrįžtamai — tik tada ši reikšmė atsilaisvins.',
    'uploaded' => 'Nepavyko įkelti :attribute.',
    'uppercase' => ':attribute turi būti parašytas (-a) didžiosiomis raidėmis.',
    'url' => ':attribute formatas yra netinkamas.',
    'ulid' => ':attribute turi būti teisingas ULID.',
    'uuid' => ':attribute turi būti teisingas UUID.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'translatable' => [
        'array' => 'Laukelis „:attribute“ turi būti pateiktas su vertimais.',
        'any' => 'Laukelis „:attribute“ turi turėti vertimą bent viena iš kalbų: :locales.',
        'all' => 'Laukelis „:attribute“ turi turėti vertimus visomis kalbomis: :locales.',
    ],

    'outside_tenant_scope' => 'Neturite teisių veikti pasirinktame padalinyje.',

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap attribute place-holders
    | with something more reader friendly such as E-Mail Address instead
    | of "email". This simply helps us make messages a little cleaner.
    |
    */

    'attributes' => [
        'email' => 'El. paštas',
        'first_name' => 'Vardas',
        'last_name' => 'Pavardė',
        'name' => 'Vardas ir pavardė (arba pavadinimas)',
        'password' => 'Slaptažodis',
        'phone' => 'Telefono numeris',
        'short_name' => 'Trumpas pavadinimas',
        'tenant_id' => 'Padalinys',
        'alias' => 'Techninė žymė',
        'link' => 'Nuoroda',
        'link_url' => 'Nuoroda',
        'title' => 'Pavadinimas',
        'text' => 'Tekstas',
        'image' => 'Paveikslėlis',
        'image_url' => 'Paveikslėlio nuoroda',
        'duties' => 'Pareigybės laukelis',
        'date' => 'Data ir laikas',
        'description' => 'Aprašymas',
        'course' => 'Kursas',
    ],
];
