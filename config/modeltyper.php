<?php

use App\Casts\DateOnly;

return [
    /*
     * Custom casts model:typer cannot infer; without a mapping they are typed as `unknown`.
     * DateOnly serialises to a `Y-m-d` string.
     */
    'custom_mappings' => [
        DateOnly::class => 'string',
    ],
];
