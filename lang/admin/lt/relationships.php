<?php

return [
    'create_new' => 'Sukurti naują ryšį',
    'create' => 'Sukurti',
    'model_type' => 'Modelio tipas',
    'select_model_type' => 'Pasirinkite modelio tipą',

    // Source and target labels
    'source' => 'Ryšio šaltinis',
    'source_hint' => 'galės matyti tikslą',
    'target' => 'Ryšio tikslas',
    'target_hint' => 'bus matomas šaltiniui',
    'select_source' => 'Pasirinkite šaltinį',
    'select_target' => 'Pasirinkite tikslą',
    'search_model' => 'Ieškoti...',
    'no_results' => 'Nieko nerasta',

    // Relationship types
    'type_based' => 'Per tipą',
    'direct' => 'Tiesioginis',

    // Model connections section
    'model_connections' => 'Modelių ryšiai',
    'no_connections' => 'Nėra ryšių',
    'no_connections_description' => 'Pridėkite ryšius tarp modelių, kad jie galėtų matyti vieni kitų duomenis.',
    'create_first' => 'Sukurti pirmąjį ryšį',
    'edit_connection' => 'Redaguoti ryšį',
    'edit_connection_description' => 'Pakeiskite ryšio nustatymus.',
    'create_new_description' => 'Sukurkite naują ryšį tarp modelių.',
    'confirm_delete' => 'Ar tikrai norite ištrinti šį ryšį?',

    // Access explanation
    'access_explanation_title' => 'Prieigos paaiškinimas',
    'access_direct' => ':source nariai galės matyti :target susitikimus, informaciją ir valdyti kai kuriuos duomenis. :target nariai nematys :source duomenų.',
    'access_type_within' => 'Visos :source tipo institucijos galės matyti :target tipo institucijų duomenis <strong>tame pačiame padalinyje</strong>.',
    'access_type_cross' => 'Visos :source tipo institucijos iš <strong>pagrindinio padalinio</strong> galės matyti :target tipo institucijų duomenis <strong>kituose padaliniuose</strong> (ir atvirkščiai). Tai leidžia centralizuotą stebėjimą.',

    // Scope explanations
    'scope_within_tenant_explanation' => 'Ryšys veiks tik tarp institucijų, kurios priklauso tam pačiam padaliniui.',
    'scope_cross_tenant_explanation' => 'Ryšys veiks tarp pagrindinio padalinio ir kitų padalinių institucijų. Tai naudinga centralizuotam valdymui.',

    // Direction labels
    'direction_outgoing' => 'Išeinantis',
    'direction_incoming' => 'Įeinantis',
    'direction_sibling' => 'Lygiagretus',

    // Authorization
    'authorized' => 'Su prieiga',
    'not_authorized' => 'Be prieigos',
    'view_only' => 'Tik peržiūra',

    // Tooltip
    'tooltip_via' => 'Per ryšį su',
    'tooltip_authorized' => 'Pilna prieiga prie duomenų',
    'tooltip_not_authorized' => 'Tik susitikimų peržiūra (be darbotvarkės)',

    // Bidirectional settings
    'bidirectional' => 'Dvikryptis ryšys',
    'bidirectional_enabled' => 'Abi pusės gali matyti viena kitos duomenis',
    'bidirectional_disabled' => 'Tik šaltinis gali matyti tikslą',
    'bidirectional_explanation' => 'Kai įjungta, abi ryšio pusės galės matyti viena kitos institucijų duomenis (susitikimus, informaciją ir t.t.). Kai išjungta, tik ryšio šaltinis matys tikslą.',
    'bidirectional_yes' => 'Dvikryptis',
    'bidirectional_no' => 'Vienkryptis',
    'access_bidirectional_note' => '↔ Abi pusės galės matyti viena kitos duomenis.',
    'access_unidirectional_note' => '→ Tik šaltinis galės matyti tikslo duomenis. Tikslas matys ryšį, bet ne duomenis.',

    // Validation
    'same_type_error' => 'Šaltinis ir tikslas negali būti tas pats tipas. To paties tipo institucijų ryšiai (sibling) konfigūruojami tipo redagavimo formoje, naudojant "Rodyti susijusias institucijas pagal tipą" nustatymą.',
];
