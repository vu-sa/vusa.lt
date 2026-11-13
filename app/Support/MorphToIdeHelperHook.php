<?php

namespace App\Support;

use Barryvdh\LaravelIdeHelper\Console\ModelsCommand;
use Barryvdh\LaravelIdeHelper\Contracts\ModelHookInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;

/**
 * Marks a model's own morphTo relations nullable, since a deleted target is absent even when its id
 * column is required. The type comes from the relation's `@return MorphTo<…>` generic.
 */
class MorphToIdeHelperHook implements ModelHookInterface
{
    public function run(ModelsCommand $command, Model $model): void
    {
        $modelFile = (new ReflectionClass($model))->getFileName();

        foreach ((new ReflectionClass($model))->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            // Inherited relations are skipped: their docblock names resolve against another file.
            if ($method->getFileName() !== $modelFile || $method->getNumberOfParameters() > 0 || ! self::returnsMorphTo($method)) {
                continue;
            }

            $command->setProperty($method->getName(), self::relatedType($method), true, null, '', true);
        }
    }

    private static function returnsMorphTo(ReflectionMethod $method): bool
    {
        $returnType = $method->getReturnType();

        return $returnType instanceof ReflectionNamedType && is_a($returnType->getName(), MorphTo::class, true);
    }

    private static function relatedType(ReflectionMethod $method): string
    {
        if (preg_match('/@return\s+MorphTo<(?:contravariant\s+)?(.+?)\s*,/', (string) $method->getDocComment(), $matches) === 1) {
            return $matches[1];
        }

        return '\\'.Model::class.'|\\Eloquent';
    }
}
