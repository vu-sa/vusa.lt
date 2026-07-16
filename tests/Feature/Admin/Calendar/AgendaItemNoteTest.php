<?php

use App\Enums\MeetingType;
use App\Models\AgendaItemNote;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\Meeting;
use App\Models\Pivots\AgendaItem;
use App\Models\Tenant;
use App\Models\Type;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::query()->first();
    $this->admin = makeTenantUserWithRole('Communication Coordinator', $this->tenant);

    $this->institution = Institution::factory()->for($this->tenant)->create();

    $startTime = Carbon::now()->addDay();
    $this->meeting = Meeting::create([
        'title' => 'Notes test meeting',
        'start_time' => $startTime->format('Y-m-d H:i'),
        'type' => MeetingType::InPerson,
    ]);
    $this->meeting->institutions()->attach($this->institution->id);

    $this->agendaItem = AgendaItem::factory()->create([
        'meeting_id' => $this->meeting->id,
    ]);
});

describe('agenda item notes API', function () {
    test('authorized user gets a note, auto-creating an empty one', function () {
        expect($this->agendaItem->note()->exists())->toBeFalse();

        $response = asUser($this->admin)
            ->getJson(route('api.v1.admin.agendaItems.note.show', $this->agendaItem->id));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.yjs_state', null);

        expect($this->agendaItem->note()->exists())->toBeTrue();
    });

    test('authorized user can persist the Y.js snapshot and HTML', function () {
        $response = asUser($this->admin)
            ->putJson(route('api.v1.admin.agendaItems.note.update', $this->agendaItem->id), [
                'yjs_state' => base64_encode('binary-yjs-update'),
                'notes_html' => '<p>Bendros pastabos</p>',
            ]);

        $response->assertOk()->assertJsonPath('success', true);

        $note = $this->agendaItem->note()->first();
        expect($note->yjs_state)->toBe(base64_encode('binary-yjs-update'));
        expect($note->notes_html)->toBe('<p>Bendros pastabos</p>');
        expect($note->updated_by)->toBe($this->admin->id);
    });

    test('update requires a yjs_state', function () {
        asUser($this->admin)
            ->putJson(route('api.v1.admin.agendaItems.note.update', $this->agendaItem->id), [
                'notes_html' => '<p>No state</p>',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['yjs_state']);
    });

    test('unauthorized user cannot read notes (403)', function () {
        $outsider = makeUser(Tenant::query()->where('id', '!=', $this->tenant->id)->first() ?? $this->tenant);

        asUser($outsider)
            ->getJson(route('api.v1.admin.agendaItems.note.show', $this->agendaItem->id))
            ->assertStatus(403);
    });

    test('unauthorized user cannot persist notes (403)', function () {
        $outsider = makeUser(Tenant::query()->where('id', '!=', $this->tenant->id)->first() ?? $this->tenant);

        asUser($outsider)
            ->putJson(route('api.v1.admin.agendaItems.note.update', $this->agendaItem->id), [
                'yjs_state' => base64_encode('x'),
            ])
            ->assertStatus(403);

        expect($this->agendaItem->note()->exists())->toBeFalse();
    });

    test('show returns the meeting active student representatives for @mentions', function () {
        $studentRepType = Type::query()->where('slug', 'studentu-atstovai')->first()
            ?? Type::factory()->create(['slug' => 'studentu-atstovai', 'model_type' => Duty::class]);

        $duty = Duty::factory()
            ->for($this->institution)
            ->hasAttached($studentRepType, [], 'types')
            ->create();

        $representative = User::factory()->create(['name' => 'Atstovė Ona']);
        $representative->duties()->attach($duty, [
            'start_date' => now()->subMonth(),
            'end_date' => null,
        ]);

        $response = asUser($this->admin)
            ->getJson(route('api.v1.admin.agendaItems.note.show', $this->agendaItem->id));

        $response->assertOk()
            ->assertJsonPath('data.representatives.0.id', (string) $representative->id)
            ->assertJsonPath('data.representatives.0.name', 'Atstovė Ona');
    });

    test('raw yjs_state is never exposed via default model serialization', function () {
        $note = AgendaItemNote::factory()->create([
            'agenda_item_id' => $this->agendaItem->id,
            'yjs_state' => base64_encode('secret-state'),
        ]);

        expect($note->toArray())->not->toHaveKey('yjs_state');
    });
});

describe('agenda item notes presence channel auth', function () {
    // The presence channel (agenda-item-notes.{id}) gates membership entirely on the
    // AgendaItem "update" ability, so asserting the Gate documents the realtime
    // security contract directly (the /broadcasting/auth HTTP path is unreliable
    // under the test broadcaster).
    test('authorized user passes the update gate that guards the channel', function () {
        expect(Gate::forUser($this->admin)->allows('update', $this->agendaItem))->toBeTrue();
    });

    test('unauthorized user fails the update gate that guards the channel', function () {
        $outsider = makeUser(Tenant::query()->where('id', '!=', $this->tenant->id)->first() ?? $this->tenant);

        expect(Gate::forUser($outsider)->allows('update', $this->agendaItem))->toBeFalse();
    });
});

describe('html sanitization', function () {
    /**
     * Notes are authored by any representative on the meeting and re-served to
     * every other participant through `v-html`, so the rendered snapshot is
     * sanitized on write. `yjs_state` stays the untouched source of truth.
     */
    test('strips script from the rendered snapshot', function () {
        asUser($this->admin)
            ->putJson(route('api.v1.admin.agendaItems.note.update', $this->agendaItem->id), [
                'yjs_state' => base64_encode('state'),
                'notes_html' => '<p>Pastaba</p><script>alert(1)</script><img src=x onerror="alert(2)">',
            ])
            ->assertOk();

        $note = AgendaItemNote::query()->where('agenda_item_id', $this->agendaItem->id)->sole();

        expect($note->notes_html)
            ->toContain('Pastaba')
            ->not->toContain('<script')
            ->not->toContain('onerror');
    });

    test('keeps legitimate formatting', function () {
        asUser($this->admin)
            ->putJson(route('api.v1.admin.agendaItems.note.update', $this->agendaItem->id), [
                'yjs_state' => base64_encode('state'),
                'notes_html' => '<h2 id="a">Tema</h2><ul><li><strong>Punktas</strong></li></ul>',
            ])
            ->assertOk();

        $note = AgendaItemNote::query()->where('agenda_item_id', $this->agendaItem->id)->sole();

        expect($note->notes_html)
            ->toContain('<h2 id="a">Tema</h2>')
            ->toContain('<strong>Punktas</strong>');
    });

    test('a null snapshot stays null', function () {
        asUser($this->admin)
            ->putJson(route('api.v1.admin.agendaItems.note.update', $this->agendaItem->id), [
                'yjs_state' => base64_encode('state'),
            ])
            ->assertOk();

        $note = AgendaItemNote::query()->where('agenda_item_id', $this->agendaItem->id)->sole();

        expect($note->notes_html)->toBeNull();
    });
});
