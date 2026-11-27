<?php

use App\Models\Traits\HasTranslations;
use App\Support\TranslatableModelIdeHelperHook;
use Barryvdh\LaravelIdeHelper\Console\ModelsCommand;
use Illuminate\Database\Eloquent\Model;

beforeEach(function (): void {
    $this->mockCommand = $this->createStub(ModelsCommand::class);
    $this->setPropertyCalls = [];

    $this->mockCommand->method('setProperty')->willReturnCallback(function ($attribute, $type) {
        $this->setPropertyCalls[] = ['attribute' => $attribute, 'type' => $type];

        return null;
    });

    $this->hook = Mockery::mock(TranslatableModelIdeHelperHook::class)->makePartial();
    $this->hook->shouldAllowMockingProtectedMethods();
    $this->hook->shouldReceive('getNullableColumns')->andReturn([
        'name' => true,
        'description' => true,
        'title' => false,
    ]);
});

describe('TranslatableModelIdeHelperHook functionality', function (): void {
    test('sets nullable and non-nullable union types for translatable attributes', function (): void {
        $modelClass = new class extends Model
        {
            use HasTranslations;

            public $translatable = ['name', 'description', 'title'];

            public function getTranslatableAttributes(): array
            {
                return $this->translatable;
            }
        };

        $this->hook->run($this->mockCommand, $modelClass);

        expect($this->setPropertyCalls)->toBe([
            ['attribute' => 'name', 'type' => 'array|string|null'],
            ['attribute' => 'description', 'type' => 'array|string|null'],
            ['attribute' => 'title', 'type' => 'array|string'],
        ]);
    });

    test('does not modify non-translatable model properties', function (): void {
        $mockModel = new class extends Model {};

        $this->hook->run($this->mockCommand, $mockModel);

        expect($this->setPropertyCalls)->toBeEmpty();
    });

    test('handles empty translatable attributes array', function (): void {
        $modelClass = new class extends Model
        {
            use HasTranslations;

            public $translatable = [];

            public function getTranslatableAttributes(): array
            {
                return $this->translatable;
            }
        };

        $this->hook->run($this->mockCommand, $modelClass);

        expect($this->setPropertyCalls)->toBeEmpty();
    });
});
