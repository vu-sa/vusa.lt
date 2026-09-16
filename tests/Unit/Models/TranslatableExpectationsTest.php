<?php

use App\Models\EventType;

describe('translatable Pest expectations', function (): void {
    it('asserts a factory builds a full translation array', function (): void {
        $eventType = EventType::factory()->make();

        expect($eventType)->toHaveTranslations('name')
            ->toHaveTranslations('description', ['lt', 'en']);
    });

    it('asserts a specific locale resolves to a non-empty string', function (): void {
        $eventType = EventType::factory()->make();

        expect($eventType)->toHaveTranslation('name', 'lt')
            ->toHaveTranslation('name', 'en');
    });
});
