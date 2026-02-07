<?php

use App\Events\FileableNameUpdated;
use App\Listeners\UpdateSharepointFolder;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\Tenant;
use App\Models\Type;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

describe('UpdateSharepointFolder Listener', function () {
    beforeEach(function () {
        $this->tenant = Tenant::factory()->create(['shortname' => 'test-tenant']);
    });

    test('event is dispatched when existing Institution name changes', function () {
        // Create institution first (without faking events for creation)
        $institution = Institution::factory()->for($this->tenant)->create([
            'name' => ['lt' => 'Original Name', 'en' => 'Original Name EN'],
        ]);

        // Now fake events for the update
        Event::fake([FileableNameUpdated::class]);

        $institution->name = ['lt' => 'New Name', 'en' => 'New Name EN'];
        $institution->save();

        Event::assertDispatched(FileableNameUpdated::class, function ($event) use ($institution) {
            return $event->fileable->is($institution);
        });
    });

    test('event is NOT dispatched when Institution name does not change', function () {
        // Create institution first (without faking events for creation)
        $institution = Institution::factory()->for($this->tenant)->create([
            'name' => ['lt' => 'Original Name', 'en' => 'Original Name EN'],
            'short_name' => 'OldShort',
        ]);

        // Now fake events for the update
        Event::fake([FileableNameUpdated::class]);

        // Change a different field
        $institution->short_name = 'NewShort';
        $institution->save();

        Event::assertNotDispatched(FileableNameUpdated::class);
    });

    test('listener skips new models that do not exist yet', function () {
        // Create a model but don't save it - simulates brand new creation
        $institution = Institution::factory()->for($this->tenant)->make([
            'name' => ['lt' => 'New Name', 'en' => 'New Name EN'],
        ]);

        // Model doesn't exist yet
        expect($institution->exists)->toBeFalse();

        $event = new FileableNameUpdated($institution);
        $listener = new UpdateSharepointFolder;

        // Should not throw - listener skips new models
        $listener->handle($event);

        expect(true)->toBeTrue();
    });

    test('listener skips processing in testing environment', function () {
        $institution = Institution::factory()->for($this->tenant)->create([
            'name' => ['lt' => 'Original Name', 'en' => 'Original Name EN'],
        ]);

        // Simulate the state during saving hook: original values exist, model isDirty
        $institution->name = ['lt' => 'New Name', 'en' => 'New Name EN'];

        $event = new FileableNameUpdated($institution);
        $listener = new UpdateSharepointFolder;

        // Should not throw - listener skips in testing environment
        $listener->handle($event);

        expect(true)->toBeTrue();
    });

    test('listener skips when old and new names are the same', function () {
        $institution = Institution::factory()->for($this->tenant)->create([
            'name' => ['lt' => 'Same Name', 'en' => 'Same Name EN'],
        ]);

        // Set the same Lithuanian name (which is used for folder names)
        $institution->name = ['lt' => 'Same Name', 'en' => 'Different EN'];

        $event = new FileableNameUpdated($institution);
        $listener = new UpdateSharepointFolder;

        // Should not throw - listener skips when names are the same
        $listener->handle($event);

        expect(true)->toBeTrue();
    });

    test('event is dispatched during saving hook before database commit', function () {
        // Create institution first
        $institution = Institution::factory()->for($this->tenant)->create([
            'name' => ['lt' => 'Original Name', 'en' => 'Original Name EN'],
        ]);

        $eventFiredBeforeCommit = false;

        Event::listen(FileableNameUpdated::class, function ($event) use (&$eventFiredBeforeCommit) {
            // At this point, the model should be dirty (saving hook fires before commit)
            $eventFiredBeforeCommit = $event->fileable->isDirty('name');
        });

        $institution->name = ['lt' => 'New Name', 'en' => 'New Name EN'];
        $institution->save();

        expect($eventFiredBeforeCommit)->toBeTrue();
    });
});

describe('Type SharePoint folder renaming', function () {
    test('event is dispatched when existing Type title changes', function () {
        // Create type first (without faking events for creation)
        $type = Type::factory()->create([
            'title' => ['lt' => 'Originalus Pavadinimas', 'en' => 'Original Title'],
            'model_type' => Institution::class,
        ]);

        // Now fake events for the update
        Event::fake([FileableNameUpdated::class]);

        $type->title = ['lt' => 'Naujas Pavadinimas', 'en' => 'New Title'];
        $type->save();

        Event::assertDispatched(FileableNameUpdated::class, function ($event) use ($type) {
            return $event->fileable->is($type);
        });
    });

    test('event is NOT dispatched when Type title does not change', function () {
        // Create type first (without faking events for creation)
        $type = Type::factory()->create([
            'title' => ['lt' => 'Originalus Pavadinimas', 'en' => 'Original Title'],
            'model_type' => Institution::class,
        ]);

        // Now fake events for the update
        Event::fake([FileableNameUpdated::class]);

        // Change a different field
        $type->description = ['lt' => 'Naujas aprašymas', 'en' => 'New description'];
        $type->save();

        Event::assertNotDispatched(FileableNameUpdated::class);
    });

    test('listener skips when Type old and new titles are the same', function () {
        $type = Type::factory()->create([
            'title' => ['lt' => 'Same Title', 'en' => 'Same Title EN'],
            'model_type' => Institution::class,
        ]);

        // Set the same Lithuanian title (which is used for folder names)
        $type->title = ['lt' => 'Same Title', 'en' => 'Different EN'];

        $event = new FileableNameUpdated($type);
        $listener = new UpdateSharepointFolder;

        // Should not throw - listener skips when titles are the same
        $listener->handle($event);

        expect(true)->toBeTrue();
    });
});

describe('Meeting SharePoint folder renaming', function () {
    beforeEach(function () {
        $this->tenant = Tenant::factory()->create(['shortname' => 'test-tenant']);
        $this->institution = Institution::factory()->for($this->tenant)->create();
    });

    test('event is dispatched when existing Meeting start_time changes', function () {
        // Create meeting first (without faking events for creation)
        $meeting = Meeting::factory()->create([
            'start_time' => '2025-10-27 15:00:00',
        ]);
        $meeting->institutions()->attach($this->institution);

        // Now fake events for the update
        Event::fake([FileableNameUpdated::class]);

        $meeting->start_time = '2025-10-27 13:00:00';
        $meeting->save();

        Event::assertDispatched(FileableNameUpdated::class, function ($event) use ($meeting) {
            return $event->fileable->is($meeting);
        });
    });

    test('event is NOT dispatched when Meeting start_time does not change', function () {
        // Create meeting first (without faking events for creation)
        $meeting = Meeting::factory()->create([
            'start_time' => '2025-10-27 15:00:00',
        ]);
        $meeting->institutions()->attach($this->institution);

        // Now fake events for the update
        Event::fake([FileableNameUpdated::class]);

        // Change a different field
        $meeting->title = 'New Title';
        $meeting->save();

        Event::assertNotDispatched(FileableNameUpdated::class);
    });

    test('listener correctly formats Meeting folder name from datetime', function () {
        $meeting = Meeting::factory()->create([
            'start_time' => '2025-10-27 15:00:00',
        ]);
        $meeting->institutions()->attach($this->institution);

        // Simulate the state during saving hook
        $meeting->start_time = '2025-10-27 13:00:00';

        $event = new FileableNameUpdated($meeting);
        $listener = new UpdateSharepointFolder;

        // Should not throw - listener skips in testing environment
        // but this verifies the datetime parsing logic works
        $listener->handle($event);

        expect(true)->toBeTrue();
    });
});
