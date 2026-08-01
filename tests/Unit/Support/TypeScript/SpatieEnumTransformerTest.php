<?php

use App\Enums\DegreeEnum;
use App\Enums\PermissionScopeEnum;
use App\Models\User;
use App\Support\TypeScript\SpatieEnumTransformer;
use Spatie\TypeScriptTransformer\Data\TransformationContext;
use Spatie\TypeScriptTransformer\PhpNodes\PhpClassNode;
use Spatie\TypeScriptTransformer\Transformed\Transformed;
use Spatie\TypeScriptTransformer\Transformed\Untransformable;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptEnum;

describe('SpatieEnumTransformer', function () {
    test('transforms a spatie/laravel-enum class into a TypeScript enum node', function () {
        $node = PhpClassNode::fromClassString(DegreeEnum::class);
        $context = TransformationContext::createFromPhpClass($node);

        $transformed = (new SpatieEnumTransformer)->transform($node, $context);

        expect($transformed)->toBeInstanceOf(Transformed::class);

        /** @var TypeScriptEnum $tsNode */
        $tsNode = $transformed->getNode();

        expect($tsNode)->toBeInstanceOf(TypeScriptEnum::class)
            ->and($tsNode->name)->toBe('DegreeEnum')
            ->and($tsNode->cases)->toContain(['name' => 'BA', 'value' => 'BA'])
            ->and($tsNode->cases)->toContain(['name' => 'PHD', 'value' => 'PhD'])
            ->and($tsNode->cases)->toContain(['name' => 'INTEGRATED_STUDIES', 'value' => 'Integrated Studies']);
    });

    test('resolves case values via the label, respecting labels() overrides', function () {
        $node = PhpClassNode::fromClassString(PermissionScopeEnum::class);
        $context = TransformationContext::createFromPhpClass($node);

        $transformed = (new SpatieEnumTransformer)->transform($node, $context);

        /** @var TypeScriptEnum $tsNode */
        $tsNode = $transformed->getNode();

        expect($tsNode->cases)->toContain(['name' => 'OWN', 'value' => 'own'])
            ->and($tsNode->cases)->toContain(['name' => 'ALL', 'value' => '*']);
    });

    test('is untransformable for classes that do not extend spatie/enum', function () {
        $node = PhpClassNode::fromClassString(User::class);
        $context = TransformationContext::createFromPhpClass($node);

        expect((new SpatieEnumTransformer)->transform($node, $context))
            ->toBeInstanceOf(Untransformable::class);
    });
});
