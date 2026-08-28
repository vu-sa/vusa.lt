<?php

use App\Support\Docs\TestSurfaceScanner;

describe('test name extraction', function (): void {
    test('prefixes each test with its describe context', function (): void {
        $names = (new TestSurfaceScanner)->testNamesIn(<<<'PHP'
        <?php
        describe('unauthorized access', function () {
            test('cannot access index page', function () {});
        });
        PHP);

        expect($names)->toBe(['unauthorized access → cannot access index page']);
    });

    test('walks nested describe blocks', function (): void {
        $names = (new TestSurfaceScanner)->testNamesIn(<<<'PHP'
        <?php
        describe('reservations', function () {
            describe('lending', function () {
                it('marks an item returned', function () {});
            });
        });
        PHP);

        expect($names)->toBe(['reservations → lending → marks an item returned']);
    });

    test('pops the describe context on the way out', function (): void {
        // A test after a describe block must not inherit that block's label.
        $names = (new TestSurfaceScanner)->testNamesIn(<<<'PHP'
        <?php
        describe('grouped', function () {
            it('inside', function () {});
        });
        it('outside', function () {});
        PHP);

        expect($names)->toBe(['grouped → inside', 'outside']);
    });

    test('keeps names a regex would truncate on an escaped apostrophe', function (): void {
        $names = (new TestSurfaceScanner)->testNamesIn(<<<'PHP'
        <?php
        it('can\'t delete a page it does not own', function () {});
        PHP);

        expect($names)->toBe(["can't delete a page it does not own"]);
    });

    test('returns nothing for a file that declares no tests', function (): void {
        expect((new TestSurfaceScanner)->testNamesIn('<?php class Foo {}'))->toBeEmpty();
    });
});
