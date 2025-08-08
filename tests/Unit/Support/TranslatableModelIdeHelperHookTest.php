<?php

use App\Support\TranslatableModelIdeHelperHook;
use Barryvdh\LaravelIdeHelper\Console\ModelsCommand;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->hook = new TranslatableModelIdeHelperHook;
    $this->mockCommand = $this->createMock(ModelsCommand::class);

    // Track setProperty calls to verify the hook behavior
    $this->setPropertyCalls = [];

    // Mock setProperty to track what the hook calls it with
    $this->mockCommand->method('setProperty')->willReturnCallback(function ($attribute, $type) {
        $this->setPropertyCalls[] = ['attribute' => $attribute, 'type' => $type];

        return null;
    });

    // Create a mock for the hook to override the protected getNullableColumns method
    $this->hook = \Mockery::mock(TranslatableModelIdeHelperHook::class)->makePartial();
    $this->hook->shouldAllowMockingProtectedMethods();
    $this->hook->shouldReceive('getNullableColumns')->andReturn([
        'name' => true,
        'description' => true,
        'title' => true,
        'field1' => true,
        'field2' => true,
        'field3' => true,
    ]);
});

describe('TranslatableModelIdeHelperHook functionality', function () {
    test('implements correct hook interface', function () {
        expect($this->hook)->toBeInstanceOf(TranslatableModelIdeHelperHook::class);
        expect(method_exists($this->hook, 'run'))->toBeTrue();
    });

    test('modifies properties for translatable models', function () {
        // Create a concrete class instance for testing
        $modelClass = new class extends \Illuminate\Database\Eloquent\Model
        {
            use \App\Models\Traits\HasTranslations;

            public $translatable = ['name', 'description', 'title'];

            public function getTranslatableAttributes(): array
            {
                return $this->translatable;
            }
        };

        // Run the hook
        $this->hook->run($this->mockCommand, $modelClass);

        // Verify that setProperty was called for each translatable attribute
        expect($this->setPropertyCalls)->toHaveCount(3);

        // Check that each translatable field was set to 'array|string|null'
        $calls = collect($this->setPropertyCalls);

        expect($calls->where('attribute', 'name')->first()['type'])->toBe('array|string|null');
        expect($calls->where('attribute', 'description')->first()['type'])->toBe('array|string|null');
        expect($calls->where('attribute', 'title')->first()['type'])->toBe('array|string|null');
    });

    test('does not modify non-translatable model properties', function () {
        // Mock a non-translatable model
        $mockModel = new class extends \Illuminate\Database\Eloquent\Model
        {
            // No translatable methods
        };

        // Run the hook
        $this->hook->run($this->mockCommand, $mockModel);

        // Verify no setProperty calls were made
        expect($this->setPropertyCalls)->toHaveCount(0);
    });

    test('handles models without getTranslatableAttributes method', function () {
        $mockModel = new class extends \Illuminate\Database\Eloquent\Model
        {
            // No getTranslatableAttributes method
        };

        // Should not throw an error
        expect(fn () => $this->hook->run($this->mockCommand, $mockModel))
            ->not->toThrow(\Exception::class);

        // No setProperty calls should be made
        expect($this->setPropertyCalls)->toHaveCount(0);
    });

    test('sets correct type for all translatable fields', function () {
        $modelClass = new class extends \Illuminate\Database\Eloquent\Model
        {
            use \App\Models\Traits\HasTranslations;

            public $translatable = ['name', 'description'];

            public function getTranslatableAttributes(): array
            {
                return $this->translatable;
            }
        };

        // Run the hook
        $this->hook->run($this->mockCommand, $modelClass);

        // Verify setProperty was called for each translatable attribute
        expect($this->setPropertyCalls)->toHaveCount(2);

        $calls = collect($this->setPropertyCalls);
        expect($calls->where('attribute', 'name')->first()['type'])->toBe('array|string|null');
        expect($calls->where('attribute', 'description')->first()['type'])->toBe('array|string|null');
    });

    test('calls setProperty for each translatable attribute', function () {
        $modelClass = new class extends \Illuminate\Database\Eloquent\Model
        {
            use \App\Models\Traits\HasTranslations;

            public $translatable = ['name'];

            public function getTranslatableAttributes(): array
            {
                return $this->translatable;
            }
        };

        // Run the hook
        $this->hook->run($this->mockCommand, $modelClass);

        // Verify setProperty was called correctly
        expect($this->setPropertyCalls)->toHaveCount(1);
        expect($this->setPropertyCalls[0]['attribute'])->toBe('name');
        expect($this->setPropertyCalls[0]['type'])->toBe('array|string|null');
    });

    test('handles empty translatable attributes array', function () {
        $modelClass = new class extends \Illuminate\Database\Eloquent\Model
        {
            use \App\Models\Traits\HasTranslations;

            public $translatable = [];

            public function getTranslatableAttributes(): array
            {
                return $this->translatable;
            }
        };

        // Should not throw an error
        expect(fn () => $this->hook->run($this->mockCommand, $modelClass))
            ->not->toThrow(\Exception::class);

        // No setProperty calls should be made
        expect($this->setPropertyCalls)->toHaveCount(0);
    });

    test('processes all translatable attributes', function () {
        $modelClass = new class extends \Illuminate\Database\Eloquent\Model
        {
            use \App\Models\Traits\HasTranslations;

            public $translatable = ['name', 'description', 'field1'];

            public function getTranslatableAttributes(): array
            {
                return $this->translatable;
            }
        };

        // Should not throw an error
        expect(fn () => $this->hook->run($this->mockCommand, $modelClass))
            ->not->toThrow(\Exception::class);

        // Verify setProperty was called for all translatable attributes
        expect($this->setPropertyCalls)->toHaveCount(3);

        $calls = collect($this->setPropertyCalls);
        expect($calls->where('attribute', 'name')->first()['type'])->toBe('array|string|null');
        expect($calls->where('attribute', 'description')->first()['type'])->toBe('array|string|null');
        expect($calls->where('attribute', 'field1')->first()['type'])->toBe('array|string|null');
    });

    test('handles models without HasTranslations trait', function () {
        $modelClass = new class extends \Illuminate\Database\Eloquent\Model
        {
            // No HasTranslations trait
        };

        // Should not throw an error
        expect(fn () => $this->hook->run($this->mockCommand, $modelClass))
            ->not->toThrow(\Exception::class);

        // No setProperty calls should be made
        expect($this->setPropertyCalls)->toHaveCount(0);
    });
});
