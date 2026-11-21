<?php

use App\Models\Institution;
use App\Models\InstitutionType;
use App\Models\Meeting;
use App\Models\News;
use App\Models\Page;
use App\Models\Pivots\AgendaItem;
use App\Models\Tag;
use App\Models\Tenant;
use App\Models\Vote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();
    $this->otherTenant = Tenant::query()->whereKeyNot($this->tenant->id)->first();
    $this->editor = makeTenantUser('Komunikacijos koordinatorius', $this->tenant);
});

test('lists only soft-deleted records of the viewer\'s tenants, shaped like the search document', function (): void {
    $trashed = Page::factory()->for($this->tenant)->create(['title' => 'Senas puslapis']);
    $trashed->delete();
    Page::factory()->for($this->tenant)->create(['title' => 'Gyvas puslapis']);
    Page::factory()->for($this->otherTenant)->create()->delete();

    asUser($this->editor)
        ->getJson(route('api.v1.admin.trash.index', ['collection' => 'pages']))
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.total', 1)
        ->assertJsonPath('data.items.0.id', (string) $trashed->id)
        ->assertJsonPath('data.items.0.title', 'Senas puslapis')
        ->assertJsonStructure(['data' => ['items' => [['deleted_at', 'force_delete_blocked_reason']], 'per_page', 'last_page']]);
});

test('searches within the trash', function (): void {
    Page::factory()->for($this->tenant)->create(['title' => 'Stipendijos'])->delete();
    Page::factory()->for($this->tenant)->create(['title' => 'Rinkimai'])->delete();

    asUser($this->editor)
        ->getJson(route('api.v1.admin.trash.index', ['collection' => 'pages', 'search' => 'Rinkimai']))
        ->assertOk()
        ->assertJsonPath('data.total', 1)
        ->assertJsonPath('data.items.0.title', 'Rinkimai');
});

test('refuses a viewer without access to the collection', function (): void {
    asUser(makeUser($this->tenant))
        ->getJson(route('api.v1.admin.trash.index', ['collection' => 'pages']))
        ->assertForbidden();
});

test('only answers for allowlisted collections', function (): void {
    asUser($this->editor)
        ->getJson('/api/v1/admin/trash/users')
        ->assertNotFound();
});

test('rejects sorting by an arbitrary column', function (): void {
    asUser($this->editor)
        ->getJson(route('api.v1.admin.trash.index', [
            'collection' => 'pages',
            'sorting' => json_encode([['id' => 'password', 'desc' => true]]),
        ]))
        ->assertUnprocessable();
});

test('trash pagination eager-loads searchable relations without N+1 queries', function (string $collection): void {
    $tags = Tag::factory()->count(2)->create();

    $makeRecord = match ($collection) {
        'pages' => function () use ($tags): Page {
            $page = Page::factory()->for($this->tenant)->create();
            $page->tags()->attach($tags->pluck('id'));
            $page->delete();

            return $page;
        },
        'news' => function () use ($tags): News {
            $news = News::factory()->for($this->tenant)->create();
            $news->tags()->attach($tags->pluck('id'));
            $news->delete();

            return $news;
        },
    };

    $makeRecord();

    asUser($this->editor)->getJson(route('api.v1.admin.trash.index', ['collection' => $collection]))->assertOk();

    DB::enableQueryLog();
    DB::flushQueryLog();

    asUser($this->editor)
        ->getJson(route('api.v1.admin.trash.index', ['collection' => $collection, 'per_page' => 10]))
        ->assertOk()
        ->assertJsonPath('data.total', 1);

    $queriesForOne = count(DB::getQueryLog());

    foreach (range(1, 3) as $_) {
        $makeRecord();
    }

    DB::flushQueryLog();

    asUser($this->editor)
        ->getJson(route('api.v1.admin.trash.index', ['collection' => $collection, 'per_page' => 10]))
        ->assertOk()
        ->assertJsonPath('data.total', 4);

    $queriesForFour = count(DB::getQueryLog());
    DB::disableQueryLog();

    expect($queriesForFour)->toBe($queriesForOne);
})->with(['pages', 'news']);

test('trash pagination eager-loads institution and meeting searchable relations', function (): void {
    $type = InstitutionType::factory()->create();
    collect(range(1, 3))->each(function () use ($type): void {
        $institution = Institution::factory()->for($this->tenant)->create();
        $institution->types()->attach($type);
        $institution->delete();
    });

    $liveInstitution = Institution::factory()->for($this->tenant)->create();
    $liveInstitution->types()->attach($type);
    collect(range(1, 3))->each(function () use ($liveInstitution): void {
        $meeting = Meeting::factory()->create(['start_time' => now()->subDay()]);
        $meeting->institutions()->attach($liveInstitution);
        $item = AgendaItem::factory()->for($meeting)->voting()->create();
        Vote::factory()->for($item, 'agendaItem')->positive()->create();
        $meeting->delete();
    });

    DB::enableQueryLog();
    DB::flushQueryLog();

    asUser($this->editor)
        ->getJson(route('api.v1.admin.trash.index', ['collection' => 'institutions', 'per_page' => 10]))
        ->assertOk()
        ->assertJsonPath('data.total', 3);

    $institutionQueries = collect(DB::getQueryLog())->pluck('query');

    DB::flushQueryLog();

    asUser($this->editor)
        ->getJson(route('api.v1.admin.trash.index', ['collection' => 'meetings', 'per_page' => 10]))
        ->assertOk()
        ->assertJsonPath('data.total', 3);

    $meetingQueries = collect(DB::getQueryLog())->pluck('query');
    DB::disableQueryLog();

    expect($institutionQueries->filter(fn (string $sql): bool => preg_match('/[`"]institution_types[`"]/', $sql) === 1))->toHaveCount(1)
        ->and($meetingQueries->filter(fn (string $sql): bool => preg_match('/[`"]votes[`"]/', $sql) === 1))->toHaveCount(1)
        ->and($meetingQueries->filter(fn (string $sql): bool => preg_match('/[`"]agenda_items[`"]/', $sql) === 1))->toHaveCount(1);
});
