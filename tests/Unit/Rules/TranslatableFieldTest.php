<?php

use App\Rules\TranslatableField;

/**
 * Run the rule and collect any failure messages it emits.
 *
 * @param  array<int, string>  $locales
 * @return array<int, string>
 */
function runTranslatableRule(mixed $value, array $locales = ['lt', 'en'], bool $requireAll = false): array
{
    $messages = [];

    (new TranslatableField($locales, $requireAll))->validate('name', $value, function (string $message) use (&$messages) {
        $messages[] = $message;
    });

    return $messages;
}

describe('TranslatableField (any locale)', function () {
    it('passes when at least one required locale is present', function () {
        expect(runTranslatableRule(['lt' => 'Pavadinimas']))->toBeEmpty();
    });

    it('passes when all locales are present', function () {
        expect(runTranslatableRule(['lt' => 'Pavadinimas', 'en' => 'Title']))->toBeEmpty();
    });

    it('fails when the value is not an array', function () {
        expect(runTranslatableRule('Pavadinimas'))->toHaveCount(1);
    });

    it('fails when no locale has a value', function () {
        expect(runTranslatableRule([]))->toHaveCount(1);
    });

    it('treats blank/whitespace-only locales as missing', function () {
        expect(runTranslatableRule(['lt' => '   ', 'en' => '']))->toHaveCount(1);
    });
});

describe('TranslatableField (all locales required)', function () {
    it('passes only when every required locale has a value', function () {
        expect(runTranslatableRule(['lt' => 'Pavadinimas', 'en' => 'Title'], ['lt', 'en'], requireAll: true))->toBeEmpty();
    });

    it('fails when a required locale is missing', function () {
        expect(runTranslatableRule(['lt' => 'Pavadinimas'], ['lt', 'en'], requireAll: true))->toHaveCount(1);
    });
});
