<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Spatie\TemporaryDirectory\TemporaryDirectory;
use Spatie\TypeScriptTransformer\TypeScriptTransformerConfig;

describe('typescript:transform command', function (): void {

    test('exports native and Spatie enum classes', function (): void {
        $directory = (new TemporaryDirectory)->create();
        $config = app(TypeScriptTransformerConfig::class);
        app()->instance(TypeScriptTransformerConfig::class, new TypeScriptTransformerConfig(
            ...array_replace(get_object_vars($config), ['outputDirectory' => $directory->path()])
        ));

        try {
            expect(Artisan::call('typescript:transform'))->toBe(0);
            $output = File::get($directory->path('enums.ts'));
        } finally {
            $directory->delete();
        }

        // A native PHP enum...
        expect($output)->toContain('export enum ApprovalDecision {')
            // ...and a spatie/laravel-enum pseudo-enum (handled by our custom
            // SpatieEnumTransformer, see App\Support\TypeScript\SpatieEnumTransformer)
            // both need to make it into the generated output.
            ->and($output)->toContain('export enum ModelEnum {')
            ->and($output)->toContain('export enum CRUDEnum {')
            ->and($output)->toContain('export enum PermissionScopeEnum {');
    });
});
