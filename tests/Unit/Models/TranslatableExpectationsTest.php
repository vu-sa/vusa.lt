<?php

use App\Models\Category;

describe('translatable Pest expectations', function () {
    it('asserts a factory builds a full translation array', function () {
        $category = Category::factory()->make();

        expect($category)->toHaveTranslations('name');
        expect($category)->toHaveTranslations('description', ['lt', 'en']);
    });

    it('asserts a specific locale resolves to a non-empty string', function () {
        $category = Category::factory()->make();

        expect($category)->toHaveTranslation('name', 'lt');
        expect($category)->toHaveTranslation('name', 'en');
    });
});
