<?php

use App\Support\Docs\TestSurfaceScanner;

beforeEach(function (): void {
    $this->fixtures = sys_get_temp_dir().'/docs-scanner-'.uniqid();
    mkdir($this->fixtures, 0777, true);
});

afterEach(function (): void {
    array_map(unlink(...), glob($this->fixtures.'/*.php') ?: []);
    @rmdir($this->fixtures);
});

function writeFixture(string $dir, string $name, string $body): void
{
    file_put_contents($dir.'/'.$name, "<?php\n\n".$body);
}

describe('route extraction', function (): void {
    test('collects route names and the files that reference them', function (): void {
        writeFixture($this->fixtures, 'AlphaTest.php', <<<'PHP'
        it('lists pages', function () {
            $this->get(route('pages.index'))->assertOk();
        });
        PHP);

        $surface = (new TestSurfaceScanner)->scan($this->fixtures);

        expect($surface->routes)->toHaveKey('pages.index')
            ->and($surface->routes['pages.index'][0])->toContain('AlphaTest.php');
    });

    test('reads names a regex would truncate on an escaped apostrophe', function (): void {
        // 26 real test names in tests/Feature carry an escaped apostrophe; a naive
        // regex stops at the backslash and loses the rest of the statement.
        writeFixture($this->fixtures, 'ApostropheTest.php', <<<'PHP'
        it('can\'t delete a page it does not own', function () {
            $this->delete(route('pages.destroy', 1))->assertForbidden();
        });
        PHP);

        $surface = (new TestSurfaceScanner)->scan($this->fixtures);

        expect($surface->routes)->toHaveKey('pages.destroy')
            ->and($surface->testCount)->toBe(1);
    });

    test('ignores routes whose name cannot be resolved statically', function (): void {
        // Understating coverage is the safe direction: this report must never
        // claim a route is tested when it isn't.
        writeFixture($this->fixtures, 'DynamicTest.php', <<<'PHP'
        it('visits a computed route', function () {
            $this->get(route($name))->assertOk();
        });
        PHP);

        $surface = (new TestSurfaceScanner)->scan($this->fixtures);

        expect($surface->routes)->toBeEmpty();
    });

    test('counts both it() and test() as tests', function (): void {
        writeFixture($this->fixtures, 'CountTest.php', <<<'PHP'
        it('one', function () {});
        test('two', function () {});
        it('three', function () {});
        PHP);

        expect((new TestSurfaceScanner)->scan($this->fixtures)->testCount)->toBe(3);
    });
});

describe('assertion depth', function (): void {
    test('records assertions that say more than "did not blow up"', function (): void {
        writeFixture($this->fixtures, 'DeepTest.php', <<<'PHP'
        it('stores a page', function () {
            $this->post(route('pages.store'))->assertDatabaseHas('pages', ['id' => 1]);
        });
        PHP);

        $surface = (new TestSurfaceScanner)->scan($this->fixtures);
        $file = array_key_first($surface->assertions);

        expect($surface->assertions[$file])->toContain('assertDatabaseHas');
    });

    test('does not count a bare status check as depth', function (): void {
        writeFixture($this->fixtures, 'ShallowTest.php', <<<'PHP'
        it('opens the page', function () {
            $this->get(route('pages.index'))->assertStatus(200);
        });
        PHP);

        expect((new TestSurfaceScanner)->scan($this->fixtures)->assertions)->toBeEmpty();
    });
});
