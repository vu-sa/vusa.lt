<?php

use App\Models\Form;
use App\Models\FormField;
use App\Models\Registration;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->tenant = Tenant::query()->first();

    $this->form = Form::factory()->published()->create([
        'tenant_id' => $this->tenant->id,
    ]);

    $this->field = FormField::factory()->create([
        'form_id' => $this->form->id,
        'type' => 'string',
        'label' => ['lt' => 'Vardas', 'en' => 'Name'],
        'is_required' => true,
    ]);
});

describe('user attribution', function () {
    test('a guest cannot attribute a registration to another user', function () {
        $victim = makeUser($this->tenant);

        $this->post(route('registrations.store', $this->form), [
            'user_id' => $victim->id,
            'data' => [
                $this->field->id => ['value' => 'Jonas Jonaitis'],
            ],
        ])->assertRedirect();

        $registration = Registration::query()->where('form_id', $this->form->id)->firstOrFail();

        expect($registration->user_id)->toBeNull();
    });

    test('an authenticated submitter cannot impersonate another user', function () {
        $submitter = makeUser($this->tenant);
        $victim = makeUser($this->tenant);

        asUser($submitter)->post(route('registrations.store', $this->form), [
            'user_id' => $victim->id,
            'data' => [
                $this->field->id => ['value' => 'Jonas Jonaitis'],
            ],
        ])->assertRedirect();

        $registration = Registration::query()->where('form_id', $this->form->id)->firstOrFail();

        expect($registration->user_id)->toBe($submitter->id);
    });

    test('the registration is still attributed to the logged in user', function () {
        $submitter = makeUser($this->tenant);

        asUser($submitter)->post(route('registrations.store', $this->form), [
            'data' => [
                $this->field->id => ['value' => 'Jonas Jonaitis'],
            ],
        ])->assertRedirect();

        $this->assertDatabaseHas('registrations', [
            'form_id' => $this->form->id,
            'user_id' => $submitter->id,
        ]);
    });
});

describe('rate limiting', function () {
    test('submissions are throttled', function () {
        $payload = [
            'data' => [
                $this->field->id => ['value' => 'Jonas Jonaitis'],
            ],
        ];

        // The route allows 10 requests per minute.
        for ($i = 0; $i < 10; $i++) {
            $this->post(route('registrations.store', $this->form), $payload)
                ->assertRedirect();
        }

        $this->post(route('registrations.store', $this->form), $payload)
            ->assertStatus(429);
    });
});
