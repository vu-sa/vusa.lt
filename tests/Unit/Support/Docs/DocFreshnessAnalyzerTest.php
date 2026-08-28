<?php

use App\Support\Docs\DocClaims;
use App\Support\Docs\DocFreshnessAnalyzer;

/**
 * A real, committed test file so `git log` returns an actual commit date.
 */
const CITED_TEST = 'tests/Feature/Admin/Content/PageControllerTest.php';

function freshnessClaims(?string $reviewedAt): DocClaims
{
    return new DocClaims(
        claims: ['docs/page.md' => [CITED_TEST]],
        unclaimedPages: [],
        meta: ['docs/page.md' => ['area' => 'pages', 'models' => [], 'reviewedAt' => $reviewedAt, 'tests' => [CITED_TEST]]],
    );
}

describe('drift detection', function (): void {
    test('flags a page reviewed before its cited tests last changed', function (): void {
        $freshness = (new DocFreshnessAnalyzer)->analyze(freshnessClaims('2020-01-01'));

        expect($freshness)->toHaveCount(1)
            ->and($freshness[0]->hasDrifted())->toBeTrue()
            ->and($freshness[0]->changedSince)->toContain(CITED_TEST)
            ->and($freshness[0]->lastChangeAt)->not->toBeNull();
    });

    test('does not flag a page reviewed after its tests last changed', function (): void {
        $freshness = (new DocFreshnessAnalyzer)->analyze(freshnessClaims('2099-01-01'));

        expect($freshness[0]->hasDrifted())->toBeFalse();
    });

    test('reports a page that cites tests but never records a review', function (): void {
        $freshness = (new DocFreshnessAnalyzer)->analyze(freshnessClaims(null));

        expect($freshness[0]->neverReviewed())->toBeTrue()
            ->and($freshness[0]->hasDrifted())->toBeFalse();
    });
});
