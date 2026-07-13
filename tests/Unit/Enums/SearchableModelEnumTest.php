<?php

use App\Enums\SearchableModelEnum;
use App\Models\Duty;

describe('SearchableModelEnum', function () {
    test('includes Duty in searchable model classes', function () {
        expect(SearchableModelEnum::getAllModelClasses())
            ->toContain(Duty::class)
            ->and(SearchableModelEnum::getTypesenseModelClasses())
            ->toContain(Duty::class);
    });

    test('exposes the duty enum label', function () {
        expect(SearchableModelEnum::DUTY()->label)->toBe('duty');
    });
});
