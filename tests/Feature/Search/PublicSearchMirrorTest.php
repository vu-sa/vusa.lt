<?php

use App\Models\Institution;
use App\Models\InstitutionType;
use App\Models\Meeting;
use App\Models\News;
use App\Models\Page;
use App\Models\PublicInstitution;
use App\Models\Traits\HasPublicSearchMirror;
use App\Settings\MeetingSettings;
use App\Support\MorphMap;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Scout\EngineManager;
use Laravel\Scout\Engines\NullEngine;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    // Records index writes so the lifecycle can be asserted without a running Typesense.
    $this->engine = new class extends NullEngine
    {
        /** @var list<array{string, string, string}> */
        public array $writes = [];

        public function update($models): void
        {
            foreach ($models as $model) {
                $this->writes[] = ['update', $model->searchableAs(), (string) $model->getScoutKey()];
            }
        }

        public function delete($models): void
        {
            foreach ($models as $model) {
                $this->writes[] = ['delete', $model->searchableAs(), (string) $model->getScoutKey()];
            }
        }

        /** @return list<array{string, string}> */
        public function writesTo(string $index): array
        {
            return collect($this->writes)
                ->filter(fn (array $write): bool => $write[1] === $index)
                ->map(fn (array $write): array => [$write[0], $write[2]])
                ->values()
                ->all();
        }
    };

    $engine = $this->engine;
    $manager = app(EngineManager::class);
    $manager->extend('typesense', fn () => $engine);
    $manager->forgetEngines();
});

test('every public search mirror is synced by its parent', function (string $mirror, string $parent): void {
    $declared = (new ReflectionMethod($parent, 'publicSearchMirror'))->invoke(null);

    expect(class_uses_recursive($parent))->toContain(HasPublicSearchMirror::class)
        ->and($declared)->toBe($mirror);
})->with(fn () => collect(MorphMap::ALIASED_TO_PARENT)->map(fn ($parent, $mirror) => [$mirror, $parent])->values()->all());

test('the parent lifecycle keeps its public mirror in the index', function (Closure $makePublic, Closure $hide): void {
    /** @var Model $model */
    $model = $makePublic();
    $mirrorClass = (new ReflectionMethod($model, 'publicSearchMirror'))->invoke(null);
    $index = (new $mirrorClass)->searchableAs();
    $key = (string) $model->getKey();

    $expectWrite = function (Closure $action, string $operation) use ($index, $key): void {
        $this->engine->writes = [];
        $action();
        expect($this->engine->writesTo($index))->toBe([[$operation, $key]]);
    };

    $expectWrite(fn () => $model->save(), 'update');
    $expectWrite(fn () => $model->delete(), 'delete');
    $expectWrite(fn () => $model->restore(), 'update');
    $expectWrite(fn () => $hide($model), 'delete');
    $expectWrite(fn () => $model->forceDelete(), 'delete');
})->with([
    'news' => [
        fn () => News::factory()->create(['draft' => false, 'publish_time' => now()->subHour()]),
        fn (News $news) => $news->update(['draft' => true]),
    ],
    'page' => [
        fn () => Page::factory()->create(['is_active' => true]),
        fn (Page $page) => $page->update(['is_active' => false]),
    ],
    'institution' => [
        fn () => Institution::factory()->create(['is_active' => true]),
        fn (Institution $institution) => $institution->update(['is_active' => false]),
    ],
    'meeting' => [
        function () {
            $publicType = InstitutionType::factory()->create();
            app(MeetingSettings::class)->fill(['public_meeting_institution_type_ids' => [$publicType->id]])->save();

            $institution = Institution::factory()->create();
            $institution->types()->attach($publicType);

            $meeting = Meeting::factory()->create();
            $meeting->institutions()->attach($institution);

            return $meeting;
        },
        function (Meeting $meeting) {
            $meeting->institutions()->detach();
            $meeting->unsetRelation('institutions')->save();
        },
    ],
]);

test('a row that is already gone is removed from the public index by key', function (): void {
    Institution::removeFromPublicSearch('01JGONEGONEGONEGONEGONEGONE');

    expect($this->engine->writesTo((new PublicInstitution)->searchableAs()))
        ->toBe([['delete', '01JGONEGONEGONEGONEGONEGONE']]);
});
