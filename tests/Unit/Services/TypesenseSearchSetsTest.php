<?php

use App\Services\Typesense\TypesenseCuration;
use App\Services\Typesense\TypesenseSynonyms;

describe('TypesenseSynonyms::buildSynonymSetItems', function () {
    test('includes every configured synonym as an item', function () {
        $items = TypesenseSynonyms::buildSynonymSetItems();

        $expected = count(TypesenseSynonyms::MULTI_WAY_SYNONYMS) + count(TypesenseSynonyms::ONE_WAY_SYNONYMS);
        expect($items)->toHaveCount($expected);
    });

    test('multi-way items carry id and synonyms without a root', function () {
        $items = collect(TypesenseSynonyms::buildSynonymSetItems());

        $vu = $items->firstWhere('id', 'vu-variants');
        expect($vu)->not->toBeNull()
            ->and($vu)->toHaveKeys(['id', 'synonyms'])
            ->and($vu)->not->toHaveKey('root')
            ->and($vu['synonyms'])->toContain('Vilniaus universitetas');
    });

    test('one-way items carry id, root and synonyms', function () {
        $items = collect(TypesenseSynonyms::buildSynonymSetItems());

        $decision = $items->firstWhere('id', 'decision-lt');
        expect($decision)->not->toBeNull()
            ->and($decision)->toHaveKeys(['id', 'root', 'synonyms'])
            ->and($decision['root'])->toBe('decision')
            ->and($decision['synonyms'])->toContain('nutarimas');
    });
});

describe('TypesenseCuration item builders', function () {
    test('pinItem builds a rule with an includes entry', function () {
        $item = TypesenseCuration::pinItem('pin-1', 'nuostatai', 'doc-42', 2);

        expect($item)->toMatchArray([
            'id' => 'pin-1',
            'rule' => ['query' => 'nuostatai', 'match' => 'exact'],
            'includes' => [['id' => 'doc-42', 'position' => 2]],
        ])->and($item)->not->toHaveKey('excludes');
    });

    test('excludeItem builds a rule with an excludes entry', function () {
        $item = TypesenseCuration::excludeItem('hide-1', 'test', 'doc-99');

        expect($item)->toMatchArray([
            'id' => 'hide-1',
            'rule' => ['query' => 'test', 'match' => 'exact'],
            'excludes' => [['id' => 'doc-99']],
        ])->and($item)->not->toHaveKey('includes');
    });
});
