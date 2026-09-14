<?php

use App\Events\DutiableChanged;
use App\Listeners\SyncContactSearchIndexes;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\Pivots\Dutiable;
use App\Models\PublicInstitution;
use App\Models\User;
use App\Services\ContactSearchIndexSynchronizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Typesense\Client;
use Typesense\Exceptions\ObjectNotFound;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    usesTypesense();
});

function indexedContactDocument(Duty|PublicInstitution|User $model): array
{
    $client = new Client(config('scout.typesense.client-settings'));

    return $client->collections[$model->searchableAs()]->documents[(string) $model->id]->retrieve();
}

test('assignment changes refresh names and contacts in the public institution index', function (): void {
    $institution = Institution::factory()->create(['is_active' => true]);
    $duty = Duty::factory()->for($institution)->create();
    $user = User::factory()->create(['name' => 'Index Assignment Member']);
    $assignment = Dutiable::factory()->forDuty($duty)->forUser($user)->create([
        'start_date' => now()->subMonth(),
        'end_date' => null,
    ]);

    app(SyncContactSearchIndexes::class)->handle(new DutiableChanged($assignment));

    expect(indexedContactDocument(PublicInstitution::findOrFail($institution->id))['current_user_names'])
        ->toContain('Index Assignment Member');

    $assignment->update(['end_date' => now()->toDateString()]);
    app(SyncContactSearchIndexes::class)->handle(new DutiableChanged($assignment));

    $publicDocument = indexedContactDocument(PublicInstitution::findOrFail($institution->id));
    $dutyDocument = indexedContactDocument($duty);
    $userDocument = indexedContactDocument($user);

    expect($publicDocument['current_user_names'])->not->toContain('Index Assignment Member')
        ->and($publicDocument['contacts'])->toBeEmpty()
        ->and($dutyDocument['current_user_names'])->toBeEmpty()
        ->and($dutyDocument['previous_user_names'])->toContain('Index Assignment Member')
        ->and($userDocument['current_duty_names'])->toBeEmpty()
        ->and($userDocument['previous_duty_names'])->toContain($duty->getTranslation('name', 'lt'));

    $assignment->delete();
    app(SyncContactSearchIndexes::class)->handle(new DutiableChanged($assignment));

    expect(indexedContactDocument($duty)['previous_user_names'])->toBeEmpty()
        ->and(indexedContactDocument($user)['previous_duty_names'])->toBeEmpty();
});

test('editing a user or duty refreshes their copied search data', function (): void {
    $institution = Institution::factory()->create(['is_active' => true]);
    $duty = Duty::factory()->for($institution)->create(['name' => ['lt' => 'Senas vaidmuo', 'en' => 'Old role']]);
    $user = User::factory()->create(['name' => 'Old Index Member']);
    $assignment = Dutiable::factory()->forDuty($duty)->forUser($user)->create([
        'start_date' => now()->subMonth(),
        'end_date' => null,
    ]);
    app(SyncContactSearchIndexes::class)->handle(new DutiableChanged($assignment));

    $user->update(['name' => 'New Index Member', 'profile_photo_path' => 'photos/new.jpg']);
    $duty->update(['name' => ['lt' => 'Naujas vaidmuo', 'en' => 'New role']]);

    $document = indexedContactDocument(PublicInstitution::findOrFail($institution->id));
    $dutyDocument = indexedContactDocument($duty);
    $userDocument = indexedContactDocument($user);

    expect($document['current_user_names'])->toContain('New Index Member')
        ->not->toContain('Old Index Member')
        ->and($document['contacts'][0]['profile_photo_path'])->toBe('photos/new.jpg')
        ->and($document['contacts'][0]['duty_name'])->toBe('Naujas vaidmuo')
        ->and($dutyDocument['current_user_names'])->toContain('New Index Member')
        ->and($userDocument['current_duty_names'])->toContain('Naujas vaidmuo');
});

test('moving a duty refreshes both institution documents', function (): void {
    $oldInstitution = Institution::factory()->create(['is_active' => true]);
    $newInstitution = Institution::factory()->create(['is_active' => true]);
    $duty = Duty::factory()->for($oldInstitution)->create();
    $user = User::factory()->create(['name' => 'Moved Index Member']);
    $assignment = Dutiable::factory()->forDuty($duty)->forUser($user)->create([
        'start_date' => now()->subMonth(),
        'end_date' => null,
    ]);
    app(SyncContactSearchIndexes::class)->handle(new DutiableChanged($assignment));

    $duty->update(['institution_id' => $newInstitution->id]);

    expect(indexedContactDocument(PublicInstitution::findOrFail($oldInstitution->id))['current_user_names'])
        ->not->toContain('Moved Index Member')
        ->and(indexedContactDocument(PublicInstitution::findOrFail($newInstitution->id))['current_user_names'])
        ->toContain('Moved Index Member');
});

test('deleting a user removes their name from related contact search documents', function (): void {
    $institution = Institution::factory()->create(['is_active' => true]);
    $duty = Duty::factory()->for($institution)->create();
    $user = User::factory()->create(['name' => 'Deleted Index Member']);
    $assignment = Dutiable::factory()->forDuty($duty)->forUser($user)->create([
        'start_date' => now()->subMonth(),
        'end_date' => null,
    ]);
    app(SyncContactSearchIndexes::class)->handle(new DutiableChanged($assignment));

    $user->delete();

    expect(indexedContactDocument(PublicInstitution::findOrFail($institution->id))['current_user_names'])
        ->not->toContain('Deleted Index Member')
        ->and(indexedContactDocument($duty)['current_user_names'])->toBeEmpty();
});

test('future assignment is not exposed in public institution contacts or current user names', function (): void {
    $institution = Institution::factory()->create(['is_active' => true]);
    $duty = Duty::factory()->for($institution)->create();
    $user = User::factory()->create(['name' => 'Future Index Member']);
    $assignment = Dutiable::factory()->forDuty($duty)->forUser($user)->create([
        'start_date' => now()->addMonth(),
        'end_date' => null,
    ]);

    expect($duty->fresh()->current_users)->toBeEmpty()
        ->and($user->fresh()->current_duties)->toBeEmpty();

    app(SyncContactSearchIndexes::class)->handle(new DutiableChanged($assignment));

    $publicDoc = indexedContactDocument(PublicInstitution::findOrFail($institution->id));

    expect($publicDoc['current_user_names'])->not->toContain('Future Index Member')
        ->and($publicDoc['contacts'])->toBeEmpty();
});

test('synchronizer unsearches an institution from public index when it is inactive', function (): void {
    $institution = Institution::factory()->create(['is_active' => true]);
    $duty = Duty::factory()->for($institution)->create();
    $user = User::factory()->create(['name' => 'Active Member']);
    $assignment = Dutiable::factory()->forDuty($duty)->forUser($user)->create([
        'start_date' => now()->subMonth(),
        'end_date' => null,
    ]);
    app(SyncContactSearchIndexes::class)->handle(new DutiableChanged($assignment));

    expect(indexedContactDocument(PublicInstitution::findOrFail($institution->id))['current_user_names'])
        ->toContain('Active Member');

    $institution->update(['is_active' => false]);
    app(ContactSearchIndexSynchronizer::class)->refreshInstitution($duty);

    $client = new Client(config('scout.typesense.client-settings'));
    expect(fn () => $client->collections[(new PublicInstitution)->searchableAs()]->documents[(string) $institution->id]->retrieve())
        ->toThrow(ObjectNotFound::class);
});

