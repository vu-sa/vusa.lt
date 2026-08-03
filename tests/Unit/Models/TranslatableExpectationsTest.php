<?php

use App\Models\Category;

describe('translatable Pest expectations', function (): void {
    it('asserts a factory builds a full translation array', function (): void {
        $category = Category::factory()->make();

        expect($category)->toHaveTranslations('name')
            ->toHaveTranslations('description', ['lt', 'en']);
    });

    it('asserts a specific locale resolves to a non-empty string', function (): void {
        $category = Category::factory()->make();

        expect($category)->toHaveTranslation('name', 'lt')
            ->toHaveTranslation('name', 'en');
    });
});
