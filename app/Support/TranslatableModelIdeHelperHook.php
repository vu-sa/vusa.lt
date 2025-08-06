<?php

namespace App\Support;

use Barryvdh\LaravelIdeHelper\Console\ModelsCommand;
use Barryvdh\LaravelIdeHelper\Contracts\ModelHookInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use ReflectionClass;
use Spatie\Translatable\HasTranslations;

/**
 * This hook helps to fix typings for translatable attributes on models.
 *
 * For more information, see this thread: https://github.com/spatie/laravel-translatable/discussions/365
 */
class TranslatableModelIdeHelperHook implements ModelHookInterface
{
    public function run(ModelsCommand $command, Model $model): void
    {
        if (! in_array(HasTranslations::class, class_uses_recursive($model))) {
            return;
        }

        // Check if model has getTranslatableAttributes method (from HasTranslations trait)
        if (! method_exists($model, 'getTranslatableAttributes')) {
            return;
        }

        // PHPStan can infer that $model has getTranslatableAttributes after method_exists check
        foreach ($model->getTranslatableAttributes() as $attribute) {
            $types = ['array', 'string'];

            $nullableColumns = $this->getNullableColumns($command);

            if (Arr::get($nullableColumns, $attribute, false)) {
                $types[] = 'null';
            }

            $command->setProperty($attribute, implode('|', $types));
        }
    }

    protected function getNullableColumns(ModelsCommand $command): array
    {
        return $this->getProtectedProperty($command, 'nullableColumns');
    }

    protected function getProtectedProperty(object|string $obj, string $prop): mixed
    {
        $reflection = new ReflectionClass($obj);
        $property = $reflection->getProperty($prop);

        return $property->getValue($obj);
    }
}
