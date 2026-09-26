<?php

use App\Models\Banner;
use App\Models\Institution;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

it('closes each partner row and centers a short last row at the design widths', function (): void {
    $tenant = Tenant::firstOrCreate(
        ['alias' => 'vusa'],
        [
            'shortname' => 'VU SA',
            'shortname_vu' => 'VU',
            'fullname' => 'Vilniaus universiteto Studentų atstovybė',
            'type' => 'pagrindinis',
        ]
    );

    Banner::factory()->for($tenant)->withoutLogo()->count(7)->create([
        'is_active' => 1,
        'lang' => 'lt',
    ]);

    $page = visitPublicSubdomain('www', '/lt/naujienos');
    $page->assertPresent('[data-slot="ruled-grid"]');

    foreach ([390 => 2, 820 => 3, 1180 => 5, 1440 => 5] as $width => $columns) {
        $page->resize($width, 900);

        foreach ([false, true] as $dark) {
            $page->script('document.documentElement.classList.toggle("dark", '.($dark ? 'true' : 'false').')');

            $geometry = $page->script(<<<'JS'
                (() => {
                    const grid = document.querySelector('[data-slot="ruled-grid"]');
                    const cells = [...grid.children];
                    const rect = grid.getBoundingClientRect();
                    return {
                        left: rect.left,
                        width: rect.width,
                        topBorder: parseFloat(getComputedStyle(grid).borderTopWidth),
                        cells: cells.map(cell => {
                            const box = cell.getBoundingClientRect();
                            const style = getComputedStyle(cell);
                            return {
                                left: box.left,
                                width: box.width,
                                right: parseFloat(style.borderRightWidth),
                                bottom: parseFloat(style.borderBottomWidth),
                                leftBorder: parseFloat(style.borderLeftWidth),
                            };
                        }),
                    };
                })()
            JS);

            expect($geometry['topBorder'])->toBe(1)
                ->and($geometry['cells'])->toHaveCount(7);

            foreach ($geometry['cells'] as $index => $cell) {
                $isRowEnd = ($index + 1) % $columns === 0 || $index === 6;
                expect($cell['leftBorder'])->toBe(1)
                    ->and($cell['bottom'])->toBe(1)
                    ->and($cell['right'])->toBe($isRowEnd ? 1 : 0)
                    ->and(abs($cell['width'] - $geometry['width'] / $columns))->toBeLessThanOrEqual(1);
            }

            $lastRowCount = 7 % $columns;
            $firstInLastRow = 7 - $lastRowCount;
            $expectedLeft = $geometry['left'] + ($geometry['width'] - $lastRowCount * $geometry['cells'][0]['width']) / 2;
            expect(abs($geometry['cells'][$firstInLastRow]['left'] - $expectedLeft))->toBeLessThanOrEqual(2);
        }
    }

    $page->assertNoJavaScriptErrors();
});

it('draws the quick-access top rule only over its tiles', function (): void {
    $user = makeAdminUser(Tenant::query()->first());
    $page = loginAsAdmin($user);
    $page->navigate('/mano');
    waitForInertiaRender($page, '[data-slot="navigation-tiles"]');

    foreach ([390, 820, 1180, 1440] as $width) {
        $page->resize($width, 900);

        $borders = $page->script(<<<'JS'
            (() => {
                const grid = document.querySelector('[data-slot="navigation-tiles"]');
                const cells = [...grid.children];
                return {
                    gridTop: parseFloat(getComputedStyle(grid).borderTopWidth),
                    cells: cells.map(cell => {
                        const style = getComputedStyle(cell);
                        return {
                            top: parseFloat(style.borderTopWidth),
                            bottom: parseFloat(style.borderBottomWidth),
                            left: parseFloat(style.borderLeftWidth),
                        };
                    }),
                };
            })()
        JS);

        expect($borders['gridTop'])->toBe(0)
            ->and($borders['cells'])->not->toBeEmpty();

        $columns = $width < 1024 ? 2 : 4;
        foreach ($borders['cells'] as $index => $cell) {
            expect($cell['top'])->toBe($index < $columns ? 1 : 0)
                ->and($cell['bottom'])->toBe(1)
                ->and($cell['left'])->toBe(1);
        }
    }

    $page->assertNoJavaScriptErrors();
});

it('wraps institution contact details without doubling their borders', function (): void {
    $tenant = Tenant::query()->where('alias', 'vusa')->firstOrFail();
    $institution = Institution::factory()->for($tenant)->create([
        'name' => ['lt' => 'Studentų atstovybė', 'en' => 'Student Representation'],
        'description' => ['lt' => '<p>Kontaktai</p>', 'en' => '<p>Contacts</p>'],
        'address' => ['lt' => 'Universiteto g. 3', 'en' => 'Universiteto St. 3'],
        'working_hours' => ['lt' => '9–17 val.', 'en' => '9 am–5 pm'],
        'phone' => '+37060000000',
        'email' => 'contact@example.com',
        'website' => 'https://example.com',
        'facebook_url' => 'https://facebook.com/example',
        'instagram_url' => 'https://instagram.com/example',
        'image_url' => null,
    ]);

    $page = visitPublicSubdomain('www', '/lt/kontaktai/id/'.$institution->id);
    $page->assertSee('Facebook')->assertSee('Instagram');

    foreach ([390 => 1, 820 => 2, 1180 => 3, 1440 => 3] as $width => $columns) {
        $page->resize($width, 900);

        $borders = $page->script(<<<'JS'
            (() => {
                const grid = document.querySelector('[data-slot="ruled-grid"]');
                return {
                    top: parseFloat(getComputedStyle(grid).borderTopWidth),
                    cells: [...grid.children].map(cell => {
                        const style = getComputedStyle(cell);
                        return {
                            right: parseFloat(style.borderRightWidth),
                            bottom: parseFloat(style.borderBottomWidth),
                            left: parseFloat(style.borderLeftWidth),
                        };
                    }),
                };
            })()
        JS);

        expect($borders['top'])->toBe(0)
            ->and($borders['cells'])->toHaveCount(7);

        foreach ($borders['cells'] as $index => $cell) {
            $isRowEnd = ($index + 1) % $columns === 0 || $index === 6;
            expect($cell['left'])->toBe(1)
                ->and($cell['bottom'])->toBe(1)
                ->and($cell['right'])->toBe($isRowEnd ? 1 : 0);
        }
    }

    $page->assertNoJavaScriptErrors();
});
