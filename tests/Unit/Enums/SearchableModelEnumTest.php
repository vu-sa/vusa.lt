<?php

use App\Enums\SearchableModelEnum;
use App\Models\Duty;

describe('SearchableModelEnum', function (): void {
    test('includes Duty in searchable model classes', function (): void {
        expect(SearchableModelEnum::getAllModelClasses())
            ->toContain(Duty::class)
            ->and(SearchableModelEnum::getTypesenseModelClasses())
            ->toContain(Duty::class);
    });

    test('exposes the duty enum label', function (): void {
        expect(SearchableModelEnum::DUTY->label())->toBe('duty');
    });
});
