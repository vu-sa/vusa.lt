<?php

use App\Models\News;
use App\Models\Tenant;
use App\Services\ModelAuthorizer;
use App\Services\TanstackTableService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = new TanstackTableService;
    $this->tenant = Tenant::query()->first();
});

describe('applySorting', function () {
    test('returns query unchanged when sorting is empty', function () {
        $query = News::query();
        $result = $this->service->applySorting($query, []);

        expect($result)->toBe($query);
    });

    test('applies direct column sort', function () {
        News::factory()->for($this->tenant)->create(['title' => 'Zebra']);
        News::factory()->for($this->tenant)->create(['title' => 'Apple']);

        $result = $this->service->applySorting(News::query(), [['id' => 'title', 'desc' => false]]);
        $titles = $result->pluck('title')->all();

        expect($titles)->toContain('Apple', 'Zebra');
    });

    test('applies descending sort', function () {
        News::factory()->for($this->tenant)->create(['title' => 'Apple']);
        News::factory()->for($this->tenant)->create(['title' => 'Zebra']);

        $result = $this->service->applySorting(News::query(), [['id' => 'title', 'desc' => true]]);
        $titles = $result->pluck('title')->all();

        expect($titles)->toContain('Zebra', 'Apple');
    });
});

describe('applyFiltering', function () {
    test('skips null and empty array filters', function () {
        News::factory()->for($this->tenant)->create(['title' => 'Test']);

        $result = $this->service->applyFiltering(News::query(), [
            'title' => null,
            'lang' => [],
        ]);

        expect($result->count())->toBeGreaterThanOrEqual(1);
    });

    test('applies direct column equality filter', function () {
        News::factory()->for($this->tenant)->create(['title' => 'Target', 'lang' => 'lt']);
        News::factory()->for($this->tenant)->create(['title' => 'Other', 'lang' => 'en']);

        $result = $this->service->applyFiltering(News::query(), ['lang' => 'lt']);

        expect($result->pluck('lang')->unique()->all())->toBe(['lt']);
    });

    test('applies array filter as whereIn', function () {
        News::factory()->for($this->tenant)->create(['lang' => 'lt']);
        News::factory()->for($this->tenant)->create(['lang' => 'en']);
        News::factory()->for($this->tenant)->create(['lang' => 'de']);

        $result = $this->service->applyFiltering(News::query(), ['lang' => ['lt', 'en']]);

        expect($result->pluck('lang')->unique()->sort()->values()->all())->toBe(['en', 'lt']);
    });

    test('applies string filter as case-insensitive like', function () {
        News::factory()->for($this->tenant)->create(['title' => 'Hello World']);
        News::factory()->for($this->tenant)->create(['title' => 'Goodbye']);

        $result = $this->service->applyFiltering(News::query(), ['title' => 'hello']);

        expect($result->count())->toBe(1);
        expect($result->first()->title)->toBe('Hello World');
    });

    test('applies relationship filter', function () {
        News::factory()->for($this->tenant)->create();

        $result = $this->service->applyFiltering(News::query(), ['tenant.id' => [$this->tenant->id]]);

        expect($result->count())->toBeGreaterThanOrEqual(1);
    });

    test('applies relationship string filter as like', function () {
        News::factory()->for($this->tenant)->create();

        $result = $this->service->applyFiltering(News::query(), ['tenant.shortname' => $this->tenant->shortname]);

        expect($result->count())->toBeGreaterThanOrEqual(1);
    });
});

describe('filter key validation', function () {
    test('ignores relationship filter with SQL injection in column name', function () {
        News::factory()->for($this->tenant)->create(['title' => 'Visible']);
        $total = News::count();

        $result = $this->service->applyFiltering(News::query(), [
            'tenant.shortname) OR 1=1 -- ' => 'x',
        ]);

        expect($result->count())->toBe($total);
    });

    test('ignores relationship filter with nonexistent column', function () {
        News::factory()->for($this->tenant)->create();
        $total = News::count();

        $result = $this->service->applyFiltering(News::query(), ['tenant.nonexistent_column' => 'x']);

        expect($result->count())->toBe($total);
    });

    test('does not invoke non-relation model methods from filter keys', function () {
        News::factory()->for($this->tenant)->create();
        $total = News::count();

        $result = $this->service->applyFiltering(News::query(), ['save.title' => 'x']);

        expect($result->count())->toBe($total)
            ->and(News::count())->toBe($total);
    });

    test('does not invoke non-relation model methods from sorting keys', function () {
        News::factory()->for($this->tenant)->create();
        $total = News::count();

        $result = $this->service->applySorting(News::query(), [['id' => 'delete.title', 'desc' => false]]);

        expect($result->count())->toBe($total)
            ->and(News::count())->toBe($total);
    });

    test('ignores relationship sort with SQL injection in column name', function () {
        News::factory()->for($this->tenant)->create();
        $total = News::count();

        $result = $this->service->applySorting(News::query(), [['id' => 'tenant.shortname) DESC -- ', 'desc' => false]]);

        expect($result->count())->toBe($total);
    });

    test('still sorts by valid relationship column', function () {
        News::factory()->for($this->tenant)->create();

        $result = $this->service->applySorting(News::query(), [['id' => 'tenant.shortname', 'desc' => false]]);

        expect($result->count())->toBeGreaterThanOrEqual(1);
    });
});

describe('applyGlobalSearch', function () {
    test('returns query unchanged when search text is empty', function () {
        $query = News::query();
        $result = $this->service->applyGlobalSearch($query, '', ['title']);

        expect($result)->toBe($query);
    });

    test('returns query unchanged when searchable columns are empty', function () {
        $query = News::query();
        $result = $this->service->applyGlobalSearch($query, 'test', []);

        expect($result)->toBe($query);
    });

    test('searches across multiple columns', function () {
        News::factory()->for($this->tenant)->create(['title' => 'Apple News', 'short' => 'Some description']);
        News::factory()->for($this->tenant)->create(['title' => 'Banana', 'short' => 'Apple description']);
        News::factory()->for($this->tenant)->create(['title' => 'Cherry', 'short' => 'Other']);

        $result = $this->service->applyGlobalSearch(News::query(), 'apple', ['title', 'short']);

        expect($result->count())->toBe(2);
    });
});

describe('applyPermissionFiltering', function () {
    test('returns query unchanged for super admin', function () {
        $admin = makeAdminUser($this->tenant);
        Auth::login($admin);

        $query = News::query();
        $authorizer = app(ModelAuthorizer::class);
        $result = $this->service->applyPermissionFiltering($query, 'tenant', 'news.read.padalinys', $authorizer);

        expect($result)->toBeInstanceOf(Builder::class);
    });
});

describe('applySoftDeleteFilter', function () {
    test('returns query unchanged for models without soft deletes', function () {
        $query = Tenant::query();
        $result = $this->service->applySoftDeleteFilter($query, true);

        expect($result)->toBe($query);
    });

    test('returns builder for soft-delete models', function () {
        $result = $this->service->applySoftDeleteFilter(News::query(), true);

        expect($result)->toBeInstanceOf(Builder::class);
    });
});
