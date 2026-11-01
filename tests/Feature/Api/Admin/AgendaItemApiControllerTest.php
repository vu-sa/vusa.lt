<?php

use App\Models\Institution;
use App\Models\Meeting;
use App\Models\Pivots\AgendaItem;
use App\Models\Tenant;
use App\Models\Vote;
use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::query()->first();
    $this->meeting = Meeting::factory()->create();
    $this->meeting->institutions()->attach(Institution::factory()->for($this->tenant)->create());
    $this->item = AgendaItem::factory()->for($this->meeting)->create(['title' => ['lt' => 'Klausimas', 'en' => 'Item']]);
    Vote::factory()->for($this->item, 'agendaItem')->create(['note' => ['lt' => 'Pastaba', 'en' => 'Note']]);
});

test('an editor receives every translation with the votes', function (): void {
    asUser(makeAdminUser($this->tenant))
        ->getJson(route('api.v1.admin.agendaItems.editable', $this->item))
        ->assertOk()
        ->assertJsonPath('data.title.en', 'Item')
        ->assertJsonPath('data.votes.0.note.lt', 'Pastaba');
});

test('a user who cannot update the item is refused', function (): void {
    asUser(makeUser(Tenant::factory()->create()))
        ->getJson(route('api.v1.admin.agendaItems.editable', $this->item))
        ->assertForbidden();
});

test('guests are refused', function (): void {
    $this->getJson(route('api.v1.admin.agendaItems.editable', $this->item))->assertUnauthorized();
});
