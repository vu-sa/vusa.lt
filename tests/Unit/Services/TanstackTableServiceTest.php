<?php

use App\Models\Duty;
use App\Models\Form;
use App\Models\Institution;
use App\Models\News;
use App\Models\ResourceCategory;
use App\Models\Tenant;
use App\Services\ModelAuthorizer;
use App\Services\TanstackTableService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->service = new TanstackTableService;
    $this->tenant = Tenant::query()->first();
});

describe('applySorting', function (): void {
    test('returns query unchanged when sorting is empty', function (): void {
        $query = News::query();
        $result = $this->service->applySorting($query, []);

        expect($result)->toBe($query);
    });

    test('applies direct column sort', function (): void {
        News::factory()->for($this->tenant)->create(['title' => 'Zebra']);
        News::factory()->for($this->tenant)->create(['title' => 'Apple']);

        $result = $this->service->applySorting(News::query(), [['id' => 'title', 'desc' => false]]);
        $titles = $result->pluck('title')->all();

        expect($titles)->toContain('Apple', 'Zebra');
    });

    test('applies descending sort', function (): void {
        News::factory()->for($this->tenant)->create(['title' => 'Apple']);
        News::factory()->for($this->tenant)->create(['title' => 'Zebra']);

        $result = $this->service->applySorting(News::query(), [['id' => 'title', 'desc' => true]]);
        $titles = $result->pluck('title')->all();

        expect($titles)->toContain('Zebra', 'Apple');
    });
});

describe('applyFiltering', function (): void {
    test('skips null and empty array filters', function (): void {
        News::factory()->for($this->tenant)->create(['title' => 'Test']);

        $result = $this->service->applyFiltering(News::query(), [
            'title' => null,
            'lang' => [],
        ]);

        expect($result->count())->toBeGreaterThanOrEqual(1);
    });

    test('applies direct column equality filter', function (): void {
        News::factory()->for($this->tenant)->create(['title' => 'Target', 'lang' => 'lt']);
        News::factory()->for($this->tenant)->create(['title' => 'Other', 'lang' => 'en']);

        $result = $this->service->applyFiltering(News::query(), ['lang' => 'lt']);

        expect($result->pluck('lang')->unique()->all())->toBe(['lt']);
    });

    test('applies array filter as whereIn', function (): void {
        News::factory()->for($this->tenant)->create(['lang' => 'lt']);
        News::factory()->for($this->tenant)->create(['lang' => 'en']);
        News::factory()->for($this->tenant)->create(['lang' => 'de']);

        $result = $this->service->applyFiltering(News::query(), ['lang' => ['lt', 'en']]);

        expect($result->pluck('lang')->unique()->sort()->values()->all())->toBe(['en', 'lt']);
    });

    test('applies string filter as case-insensitive like', function (): void {
        News::factory()->for($this->tenant)->create(['title' => 'Hello World']);
        News::factory()->for($this->tenant)->create(['title' => 'Goodbye']);

        $result = $this->service->applyFiltering(News::query(), ['title' => 'hello']);

        expect($result->count())->toBe(1)
            ->and($result->first()->title)->toBe('Hello World');
    });

    test('applies relationship filter', function (): void {
        News::factory()->for($this->tenant)->create();

        $result = $this->service->applyFiltering(News::query(), ['tenant.id' => [$this->tenant->id]]);

        expect($result->count())->toBeGreaterThanOrEqual(1);
    });

    test('applies relationship string filter as like', function (): void {
        News::factory()->for($this->tenant)->create();

        $result = $this->service->applyFiltering(News::query(), ['tenant.shortname' => $this->tenant->shortname]);

        expect($result->count())->toBeGreaterThanOrEqual(1);
    });
});

describe('filter key validation', function (): void {
    test('ignores relationship filter with SQL injection in column name', function (): void {
        News::factory()->for($this->tenant)->create(['title' => 'Visible']);
        $total = News::count();

        $result = $this->service->applyFiltering(News::query(), [
            'tenant.shortname) OR 1=1 -- ' => 'x',
        ]);

        expect($result->count())->toBe($total);
    });

    test('ignores relationship filter with nonexistent column', function (): void {
        News::factory()->for($this->tenant)->create();
        $total = News::count();

        $result = $this->service->applyFiltering(News::query(), ['tenant.nonexistent_column' => 'x']);

        expect($result->count())->toBe($total);
    });

    test('does not invoke non-relation model methods from filter keys', function (): void {
        News::factory()->for($this->tenant)->create();
        $total = News::count();

        $result = $this->service->applyFiltering(News::query(), ['save.title' => 'x']);

        expect($result->count())->toBe($total)
            ->and(News::count())->toBe($total);
    });

    test('does not invoke non-relation model methods from sorting keys', function (): void {
        News::factory()->for($this->tenant)->create();
        $total = News::count();

        $result = $this->service->applySorting(News::query(), [['id' => 'delete.title', 'desc' => false]]);

        expect($result->count())->toBe($total)
            ->and(News::count())->toBe($total);
    });

    test('ignores relationship sort with SQL injection in column name', function (): void {
        News::factory()->for($this->tenant)->create();
        $total = News::count();

        $result = $this->service->applySorting(News::query(), [['id' => 'tenant.shortname) DESC -- ', 'desc' => false]]);

        expect($result->count())->toBe($total);
    });

    test('still sorts by valid relationship column', function (): void {
        News::factory()->for($this->tenant)->create();

        $result = $this->service->applySorting(News::query(), [['id' => 'tenant.shortname', 'desc' => false]]);

        expect($result->count())->toBeGreaterThanOrEqual(1);
    });

    test('keeps the model\'s own id when sorting through a joined relation', function (): void {
        $news = News::factory()->for($this->tenant)->create();

        $sorted = $this->service->applySorting(News::query()->whereKey($news->id), [['id' => 'tenant.shortname', 'desc' => false]])->sole();

        expect($sorted->id)->toBe($news->id);
    });

    test('ignores sorts through has-many relations instead of joining them', function (): void {
        $query = $this->service->applySorting(Tenant::query(), [['id' => 'duties.name', 'desc' => false]]);

        expect($query->getQuery()->joins)->toBeEmpty()
            ->and($query->getQuery()->orders)->toBeEmpty();
        $query->get();
    });

    test('ignores a direct sort on a column the table does not have', function (): void {
        $query = $this->service->applySorting(News::query(), [['id' => 'no_such_column', 'desc' => false]]);

        expect($query->getQuery()->orders)->toBeEmpty();
    });

    test('qualifies direct columns so relation joins cannot make them ambiguous', function (): void {
        $query = $this->service->applySorting(News::query(), [
            ['id' => 'tenant.shortname', 'desc' => false],
            ['id' => 'created_at', 'desc' => true],
        ]);

        expect(collect($query->getQuery()->orders)->pluck('column'))->toContain('news.created_at');
        $query->get();
    });

    test('sorts translatable columns by the current locale rather than the stored JSON', function (string $locale, array $expected): void {
        app()->setLocale($locale);
        ResourceCategory::factory()->create(['name' => ['lt' => 'Ąžuolas', 'en' => 'Zebra']]);
        ResourceCategory::factory()->create(['name' => ['lt' => 'Žirgas', 'en' => 'Apple']]);

        $names = $this->service->applySorting(ResourceCategory::query(), [['id' => 'name', 'desc' => false]])
            ->get()
            ->map(fn (ResourceCategory $category) => $category->getTranslation('name', 'en'))
            ->all();

        expect($names)->toBe($expected);
    })->with([
        'lt' => ['lt', ['Zebra', 'Apple']],
        'en' => ['en', ['Apple', 'Zebra']],
    ]);

    test('sorts by an alias the query selects, such as a withCount result', function (): void {
        $query = $this->service->applySorting(
            Form::query()->withCount('registrations'),
            [['id' => 'registrations_count', 'desc' => true]],
        );

        expect(collect($query->getQuery()->orders)->pluck('column')->all())->toBe(['registrations_count']);
        $query->get();
    });
});

describe('applyGlobalSearch', function (): void {
    test('searches translated names in both configured locales regardless of the interface language', function (string $locale, string $term): void {
        app()->setLocale($locale);
        $duty = Duty::factory()->create(['name' => ['lt' => 'Stipendijos', 'en' => 'Scholarships']]);

        $result = $this->service->applyGlobalSearch(Duty::query(), $term, ['name']);

        expect($result->pluck('id')->all())->toBe([$duty->id]);
    })->with([
        'English name from Lithuanian UI' => ['lt', 'SCHOLAR'],
        'Lithuanian name from English UI' => ['en', 'stipend'],
    ]);

    test('searches translated values rather than JSON keys or encoding', function (array $names, string $term, bool $matches): void {
        $duty = Duty::factory()->create(['name' => $names]);

        $result = $this->service->applyGlobalSearch(Duty::query(), $term, ['name']);

        expect($result->pluck('id')->all())->toBe($matches ? [$duty->id] : []);
    })->with([
        'locale key lt' => [['lt' => 'Vadovas', 'en' => 'Chair'], 'lt', false],
        'locale key en' => [['lt' => 'Vadovas', 'en' => 'Chair'], 'en', false],
        'quote in translated text' => [['lt' => 'Klubas "ABC"', 'en' => 'Club "ABC"'], '"ABC"', true],
        'only English translation' => [['en' => 'Scholarships'], 'scholar', true],
        'no translations' => [[], 'scholar', false],
        'LIKE wildcard' => [['lt' => 'Vadovas', 'en' => 'Chair'], 'Ch_ir', true],
    ]);

    test('keeps translated and plain column alternatives inside the scoped trash search', function (): void {
        $institution = Institution::factory()->for($this->tenant)->create();
        $byName = Duty::factory()->for($institution)->create([
            'name' => ['lt' => 'Vadovas', 'en' => 'Scholarships'],
            'email' => 'a@example.test',
        ]);
        $byEmail = Duty::factory()->for($institution)->create([
            'name' => ['lt' => 'Vadovas', 'en' => 'Chair'],
            'email' => 'scholar@example.test',
        ]);
        Duty::factory()->for($institution)->create(['name' => ['lt' => 'Stipendijos', 'en' => 'Scholarships']]);
        $outside = Duty::factory()->create(['name' => ['lt' => 'Stipendijos', 'en' => 'Scholarships']]);
        $byName->delete();
        $byEmail->delete();
        $outside->delete();

        $query = Duty::onlyTrashed()->whereBelongsTo($institution);
        $result = $this->service->applyGlobalSearch($query, 'scholar', ['email', 'name']);

        expect($result->pluck('id')->sort()->values()->all())->toBe(collect([$byName->id, $byEmail->id])->sort()->values()->all());
    });

    test('searches related translated names while retaining the parent restriction', function (): void {
        $institution = Institution::factory()->for($this->tenant)->create([
            'name' => ['lt' => 'Taryba', 'en' => 'Scholarships Board'],
        ]);
        $duty = Duty::factory()->for($institution)->create();
        Duty::factory()->for($institution)->create();

        $result = $this->service->applyGlobalSearch(Duty::query()->whereKey($duty->id), 'scholar', ['name', 'institution.name']);

        expect($result->pluck('id')->all())->toBe([$duty->id]);
    });

    test('returns query unchanged when search text is empty', function (): void {
        $query = News::query();
        $result = $this->service->applyGlobalSearch($query, '', ['title']);

        expect($result)->toBe($query);
    });

    test('returns query unchanged when searchable columns are empty', function (): void {
        $query = News::query();
        $result = $this->service->applyGlobalSearch($query, 'test', []);

        expect($result)->toBe($query);
    });

    test('searches across multiple columns', function (): void {
        News::factory()->for($this->tenant)->create(['title' => 'Apple News', 'short' => 'Some description']);
        News::factory()->for($this->tenant)->create(['title' => 'Banana', 'short' => 'Apple description']);
        News::factory()->for($this->tenant)->create(['title' => 'Cherry', 'short' => 'Other']);

        $result = $this->service->applyGlobalSearch(News::query(), 'apple', ['title', 'short']);

        expect($result->count())->toBe(2);
    });
});

describe('applyPermissionFiltering', function (): void {
    test('returns query unchanged for super admin', function (): void {
        $admin = makeAdminUser($this->tenant);
        Auth::login($admin);

        $query = News::query();
        $authorizer = app(ModelAuthorizer::class);
        $result = $this->service->applyPermissionFiltering($query, 'tenant', 'news.read.padalinys', $authorizer);

        expect($result)->toBeInstanceOf(Builder::class);
    });
});

describe('applySoftDeleteFilter', function (): void {
    test('returns query unchanged for models without soft deletes', function (): void {
        $query = Tenant::query();
        $result = $this->service->applySoftDeleteFilter($query, true);

        expect($result)->toBe($query);
    });

    test('returns query unchanged when showDeleted is false', function (): void {
        $query = News::query();
        $result = $this->service->applySoftDeleteFilter($query, false);

        expect($result)->toBe($query)
            ->and($result->toSql())->toContain('deleted_at')
            ->and($result->toSql())->toContain('is null');
    });

    // Regression: withTrashed()/onlyTrashed() are Builder macros, so the previous
    // method_exists($query, 'withTrashed') guard was always false and silently
    // disabled the whole "show deleted" feature.
    test('restricts the query to trashed records only when showDeleted is true', function (): void {
        $live = News::factory()->for($this->tenant)->create(['title' => 'Live article']);
        $trashed = News::factory()->for($this->tenant)->create(['title' => 'Trashed article']);
        $trashed->delete();

        $result = $this->service->applySoftDeleteFilter(News::query(), true);
        $ids = $result->pluck('id')->all();

        expect($result->toSql())->toContain('deleted_at')
            ->and($result->toSql())->toContain('is not null')
            ->and($ids)->toContain($trashed->id)
            ->and($ids)->not->toContain($live->id);
    });

    test('detects soft deletes through traits composed on the model', function (): void {
        expect($this->service->isSoftDeletable(News::query()))->toBeTrue()
            ->and($this->service->isSoftDeletable(Tenant::query()))->toBeFalse();
    });
});

describe('getTrashedCount', function (): void {
    test('returns zero for models without soft deletes', function (): void {
        expect($this->service->getTrashedCount(Tenant::query()))->toBe(0);
    });

    test('counts only trashed records', function (): void {
        News::factory()->for($this->tenant)->count(2)->create();
        $trashed = News::factory()->for($this->tenant)->count(3)->create();
        $trashed->each->delete();

        expect($this->service->getTrashedCount(News::query()))->toBe(3);
    });

    test('respects filters already applied to the query', function (): void {
        $keptTenant = $this->tenant;
        $otherTenant = Tenant::query()->where('id', '!=', $keptTenant->id)->first();

        News::factory()->for($keptTenant)->create()->delete();
        News::factory()->for($otherTenant)->create()->delete();

        $scoped = News::query()->where('tenant_id', $keptTenant->id);

        expect($this->service->getTrashedCount($scoped))->toBe(1);
    });

    test('is idempotent when the query is already restricted to trashed records', function (): void {
        News::factory()->for($this->tenant)->create()->delete();

        $trashedQuery = $this->service->applySoftDeleteFilter(News::query(), true);

        expect($this->service->getTrashedCount($trashedQuery))->toBe(1);
    });

    test('leaves the original query untouched', function (): void {
        News::factory()->for($this->tenant)->create();
        News::factory()->for($this->tenant)->create()->delete();

        $query = News::query();
        $this->service->getTrashedCount($query);

        expect($query->count())->toBe(1);
    });
});
