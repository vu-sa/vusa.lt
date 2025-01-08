<?php

use Spatie\TypeScriptTransformer\Formatters\PrettierFormatter;

// NOTE: Unedited variables were removed
return [
    /*
     * The package will write the generated TypeScript to this file.
     */

    'output_file' => resource_path('js/Types/enums.ts'),

    /*
     * When the package is writing types to the output file, a writer is used to
     * determine the format. By default, this is the `TypeDefinitionWriter`.
     * But you can also use the `ModuleWriter` or implement your own.
     */

    'writer' => Spatie\TypeScriptTransformer\Writers\ModuleWriter::class,

    /*
     * The generated TypeScript file can be formatted. We ship a Prettier formatter
     * out of the box: `PrettierFormatter` but you can also implement your own one.
     * The generated TypeScript will not be formatted when no formatter was set.
     */

    'formatter' => PrettierFormatter::class,

    /*
     * Enums can be transformed into types or native TypeScript enums, by default
     * the package will transform them to types.
     */

    'transform_to_native_enums' => true,
];
