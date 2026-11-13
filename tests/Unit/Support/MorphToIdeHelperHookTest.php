<?php

use App\Models\Activity;
use App\Models\LearningProgress;
use App\Models\Task;
use App\Support\MorphToIdeHelperHook;
use Barryvdh\LaravelIdeHelper\Console\ModelsCommand;
use Illuminate\Database\Eloquent\Model;

/** @return array<string, array{type: string, nullable: bool}> */
function morphToHookProperties(Model $model): array
{
    $properties = [];
    $command = test()->createStub(ModelsCommand::class);
    $command->method('setProperty')->willReturnCallback(function ($name, $type, $read, $write, $comment, $nullable) use (&$properties): void {
        $properties[$name] = ['type' => $type, 'nullable' => $nullable];
    });

    (new MorphToIdeHelperHook)->run($command, $model);

    return $properties;
}

test('a morph target is nullable and typed from the relation generic', function (): void {
    expect(morphToHookProperties(new LearningProgress))->toBe([
        'progressable' => ['type' => 'LearningStep|KnowledgeItem', 'nullable' => true],
    ]);
});

test('a morph target without a generic falls back to Model', function (): void {
    expect(morphToHookProperties(new Task))->toBe([
        'taskable' => ['type' => '\\'.Model::class.'|\\Eloquent', 'nullable' => true],
    ]);
});

test('inherited morph relations are left to ide-helper', function (): void {
    expect(morphToHookProperties(new Activity))->toHaveKeys(['rootSubject'])->not->toHaveKeys(['subject', 'causer']);
});
