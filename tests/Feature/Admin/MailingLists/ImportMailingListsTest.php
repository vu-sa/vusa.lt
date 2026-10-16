<?php

use App\Actions\ImportMailingLists;
use App\Jobs\SyncManagedMailingLists;
use App\Models\MailingList;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

pest()->use(RefreshDatabase::class);

it('imports new manual lists and refreshes metadata without replacing managed sources or ownership', function (): void {
    Queue::fake([SyncManagedMailingLists::class]);
    config(['services.mailman.enabled' => true, 'services.mailman.api_url' => 'https://konf.test/3.1', 'services.mailman.username' => 'app']);
    Http::preventStrayRequests();
    $tenant = Tenant::query()->where('id', '!=', Tenant::main()->id)->first();
    $managed = MailingList::factory()->managed()->for($tenant)->create(['address' => 'existing@konf.vusa.lt', 'mailman_list_id' => 'existing.konf.vusa.lt']);
    $source = $managed->sources()->create(['type' => 'email', 'email' => 'keep@vusa.lt']);
    Http::fake([
        'https://konf.test/3.1/lists?*' => Http::response(['total_size' => 2, 'entries' => [
            ['fqdn_listname' => 'existing@konf.vusa.lt', 'list_id' => 'existing.konf.vusa.lt'],
            ['fqdn_listname' => 'new@konf.vusa.lt', 'list_id' => 'new.konf.vusa.lt'],
        ]]),
        'https://konf.test/3.1/lists/*/config' => Http::response([
            'display_name' => 'Remote name', 'description' => 'Remote description', 'archive_policy' => 'private',
            'send_welcome_message' => false, 'send_goodbye_message' => false,
        ]),
        'https://konf.test/3.1/lists/new.konf.vusa.lt/roster/member*' => Http::response(['total_size' => 1, 'entries' => [
            ['email' => 'member@vusa.lt', 'display_name' => 'Member', 'member_id' => 'member-1'],
        ]]),
    ]);

    expect(app(ImportMailingLists::class)->execute())->toBe(['created' => 1, 'updated' => 1]);
    expect($managed->fresh()->tenant_id)->toBe($tenant->id)
        ->and($managed->fresh()->isManaged())->toBeTrue()
        ->and($managed->fresh()->display_name)->toBe('Remote name')
        ->and($source->fresh())->not->toBeNull();
    $new = MailingList::query()->where('address', 'new@konf.vusa.lt')->sole();
    expect($new->isManaged())->toBeFalse()
        ->and($new->tenant_id)->toBe(Tenant::main()->id)
        ->and($new->members()->sole()->email)->toBe('member@vusa.lt');
    Http::assertNotSent(fn (Request $request) => $request->method() !== 'GET');
    Queue::assertPushed(SyncManagedMailingLists::class);
});
