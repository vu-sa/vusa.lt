<?php

namespace App\Support\TypeScript;

use Spatie\Enum\Enum;
use Spatie\TypeScriptTransformer\Data\TransformationContext;
use Spatie\TypeScriptTransformer\PhpNodes\PhpClassNode;
use Spatie\TypeScriptTransformer\References\PhpClassReference;
use Spatie\TypeScriptTransformer\Transformed\Transformed;
use Spatie\TypeScriptTransformer\Transformed\Untransformable;
use Spatie\TypeScriptTransformer\Transformers\Transformer;
use Spatie\TypeScriptTransformer\TypeScriptNodes\TypeScriptEnum;

/**
 * Transforms `spatie/laravel-enum` classes (class-based pseudo-enums using
 * `@method static self CASE_NAME()` docblock tags) into TypeScript enums.
 *
 * Replaces the `SpatieEnumTransformer` that shipped with `spatie/typescript-transformer` v2,
 * which was removed in v3. Case values are resolved by calling the real, autoloaded enum
 * class (`$class::CASE_NAME()->label`) instead of re-implementing `labels()`/`values()`
 * resolution, so any overrides (e.g. `HasCamelCaseLabels`) keep working automatically.
 */
class SpatieEnumTransformer implements Transformer
{
    public function transform(PhpClassNode $phpClassNode, TransformationContext $context): Transformed|Untransformable
    {
        if ($phpClassNode->isEnum()) {
            return Untransformable::create();
        }

        if (! $phpClassNode->reflection->isSubclassOf(Enum::class)) {
            return Untransformable::create();
        }

        $docComment = $phpClassNode->getDocComment();

        if ($docComment === null || ! str_contains($docComment, '@typescript')) {
            return Untransformable::create();
        }

        preg_match_all('/@method\s+static\s+self\s+(\w+)\(\)/', $docComment, $matches);

        $caseNames = $matches[1];

        if (empty($caseNames)) {
            return Untransformable::create();
        }

        /** @var class-string<Enum> $class */
        $class = $phpClassNode->getName();

        $cases = array_map(
            fn (string $caseName) => [
                'name' => $caseName,
                'value' => $class::{$caseName}()->label,
            ],
            $caseNames,
        );

        return new Transformed(
            new TypeScriptEnum($context->name, $cases),
            new PhpClassReference($phpClassNode),
            $context->nameSpaceSegments,
            true,
        );
    }
}
