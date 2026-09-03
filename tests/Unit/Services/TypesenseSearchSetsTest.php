<?php

use App\Services\Typesense\TypesenseCuration;
use App\Services\Typesense\TypesenseSynonyms;

test('global search set names use the Scout environment prefix', function (): void {
    config(['scout.prefix' => 'staging_']);

    expect(TypesenseSynonyms::setName())->toBe('staging_vusa_synonyms')
        ->and(TypesenseCuration::setName())->toBe('staging_vusa_curation');

    config(['scout.prefix' => '']);

    expect(TypesenseSynonyms::setName())->toBe('vusa_synonyms')
        ->and(TypesenseCuration::setName())->toBe('vusa_curation');
});

describe('TypesenseSynonyms::buildSynonymSetItems', function (): void {
    test('includes every configured synonym as an item', function (): void {
        $items = TypesenseSynonyms::buildSynonymSetItems();

        $expected = count(TypesenseSynonyms::MULTI_WAY_SYNONYMS) + count(TypesenseSynonyms::ONE_WAY_SYNONYMS);
        expect($items)->toHaveCount($expected);
    });

    test('multi-way items carry id and synonyms without a root', function (): void {
        $items = collect(TypesenseSynonyms::buildSynonymSetItems());

        $vu = $items->firstWhere('id', 'vu-variants');
        expect($vu)->not->toBeNull()
            ->and($vu)->toHaveKeys(['id', 'synonyms'])
            ->and($vu)->not->toHaveKey('root')
            ->and($vu['synonyms'])->toContain('Vilniaus universitetas');
    });

    test('one-way items carry id, root and synonyms', function (): void {
        $items = collect(TypesenseSynonyms::buildSynonymSetItems());

        $decision = $items->firstWhere('id', 'decision-lt');
        expect($decision)->not->toBeNull()
            ->and($decision)->toHaveKeys(['id', 'root', 'synonyms'])
            ->and($decision['root'])->toBe('decision')
            ->and($decision['synonyms'])->toContain('nutarimas');
    });
});

describe('TypesenseCuration item builders', function (): void {
    test('pinItem builds a rule with an includes entry', function (): void {
        $item = TypesenseCuration::pinItem('pin-1', 'nuostatai', 'doc-42', 2);

        expect($item)->toMatchArray([
            'id' => 'pin-1',
            'rule' => ['query' => 'nuostatai', 'match' => 'exact'],
            'includes' => [['id' => 'doc-42', 'position' => 2]],
        ])->and($item)->not->toHaveKey('excludes');
    });

    test('excludeItem builds a rule with an excludes entry', function (): void {
        $item = TypesenseCuration::excludeItem('hide-1', 'test', 'doc-99');

        expect($item)->toMatchArray([
            'id' => 'hide-1',
            'rule' => ['query' => 'test', 'match' => 'exact'],
            'excludes' => [['id' => 'doc-99']],
        ])->and($item)->not->toHaveKey('includes');
    });
});
