<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

describe('typescript:transform command', function () {
    test('runs successfully and is configured', function () {
        $exitCode = Artisan::call('typescript:transform');

        expect($exitCode)->toBe(0)
            ->and(Artisan::output())->not->toContain('TypeScript Transformer is not configured');
    });

    test('exports every declared enum class, including spatie/laravel-enum ones', function () {
        Artisan::call('typescript:transform');

        $output = File::get(resource_path('js/Types/enums.ts'));

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
