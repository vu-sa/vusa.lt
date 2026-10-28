<?php

use App\Events\MemberRegistrationCreated;
use App\Events\StudentRepRegistrationCreated;
use App\Models\FieldResponse;
use App\Models\Form;
use App\Models\FormField;
use App\Models\Institution;
use App\Models\Registration;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->form = Form::factory()->published()->create(['tenant_id' => Tenant::query()->first()->id]);
    $this->field = FormField::factory()->for($this->form)->create([
        'type' => 'string',
        'is_required' => true,
        'label' => ['lt' => 'Atsakymas', 'en' => 'Answer'],
    ]);
});

test('registration values are validated by their field definition', function (string $type, mixed $value, bool $valid, ?string $subtype = null): void {
    $this->field->update(['type' => $type, 'subtype' => $subtype, 'options' => [['value' => 0, 'label' => ['lt' => 'Nulis', 'en' => 'Zero']]]]);
    $response = $this->postJson(route('registrations.store', $this->form), ['data' => [$this->field->id => ['value' => $value]]]);

    if ($valid) {
        $response->assertRedirect();
        expect(Registration::query()->count())->toBe(1)
            ->and(FieldResponse::query()->first()->response['value'])->toBe($value);
    } else {
        $response->assertUnprocessable()->assertJsonValidationErrors("data.{$this->field->id}.value");
        expect(Registration::query()->count())->toBe(0);
    }
})->with([
    'string' => ['string', 'Jonas', true],
    'legacy text' => ['text', 'Long answer', true],
    'required whitespace' => ['string', '  ', false],
    'text object' => ['string', ['nested'], false],
    'email subtype' => ['string', 'jonas@example.com', true, 'email'],
    'invalid email subtype' => ['string', 'invalid', false, 'email'],
    'legacy email' => ['email', 'invalid', false],
    'number zero' => ['number', 0, true],
    'numeric string' => ['number', '0', true],
    'invalid number' => ['number', 'invalid', false],
    'consent' => ['boolean', true, true],
    'unchecked consent' => ['boolean', false, false],
    'invalid boolean' => ['boolean', 'not a boolean', false],
    'date' => ['date', '2026-10-27', true],
    'invalid date' => ['date', 'not a date', false],
    'invalid calendar date' => ['date', '2026-02-30', false],
    'zero option' => ['enum', 0, true],
    'legacy select' => ['select', '0', true],
    'invalid option' => ['enum', 'unknown', false],
    'option collection' => ['enum', [0], false],
]);

test('optional fields accept blank values and false', function (string $type, mixed $value): void {
    $this->field->update(['type' => $type, 'is_required' => false]);
    $this->postJson(route('registrations.store', $this->form), ['data' => [$this->field->id => ['value' => $value]]])->assertRedirect();
})->with([['boolean', false], ['number', null], ['string', null], ['date', null], ['enum', null]]);

test('malformed and foreign field responses are rejected without writes', function (string $shape): void {
    $data = match ($shape) {
        'scalar data' => 'invalid',
        'scalar response' => [$this->field->id => 'invalid'],
        'missing value' => [$this->field->id => []],
        'extra attributes' => [$this->field->id => ['value' => 'valid', 'unexpected' => true]],
        'foreign field' => [$this->field->id => ['value' => 'valid'], FormField::factory()->create()->id => ['value' => 'foreign']],
    };
    $this->postJson(route('registrations.store', $this->form), ['data' => $data])->assertUnprocessable();
    expect(Registration::query()->count())->toBe(0);
})->with(['scalar data', 'scalar response', 'missing value', 'extra attributes', 'foreign field']);

test('optional responses still require a value key when supplied', function (): void {
    $this->field->update(['is_required' => false]);
    $this->postJson(route('registrations.store', $this->form), ['data' => [$this->field->id => []]])
        ->assertUnprocessable()->assertJsonValidationErrors("data.{$this->field->id}.value");
});

test('precognition validates only the requested field and has no side effects', function (): void {
    Event::fake([MemberRegistrationCreated::class, StudentRepRegistrationCreated::class]);
    Mail::fake();
    Notification::fake();
    $other = FormField::factory()->for($this->form)->create(['type' => 'email', 'is_required' => true]);
    $this->withHeaders(['Precognition' => 'true', 'Precognition-Validate-Only' => "data.{$this->field->id}.value"])
        ->postJson(route('registrations.store', $this->form), ['data' => [$this->field->id => ['value' => 'valid']]])
        ->assertNoContent()->assertHeader('Precognition-Success', 'true');
    expect(Registration::query()->count())->toBe(0)->and(FieldResponse::query()->count())->toBe(0);
    Event::assertNotDispatched(MemberRegistrationCreated::class);
    Event::assertNotDispatched(StudentRepRegistrationCreated::class);
    Mail::assertNothingSent();
    Notification::assertNothingSent();

    $this->flushHeaders()->postJson(route('registrations.store', $this->form), ['data' => [$this->field->id => ['value' => 'valid']]])
        ->assertUnprocessable()->assertJsonValidationErrors("data.{$other->id}.value");
});

test('precognition returns localized field errors', function (string $locale, string $label): void {
    app()->setLocale($locale);
    $this->withHeaders(['Precognition' => 'true', 'Precognition-Validate-Only' => "data.{$this->field->id}.value"])
        ->postJson(route('registrations.store', $this->form), ['data' => [$this->field->id => ['value' => null]]])
        ->assertUnprocessable()->assertJsonValidationErrors("data.{$this->field->id}.value")
        ->assertSee($label);
})->with([['lt', 'Atsakymas'], ['en', 'Answer']]);

test('unpublished forms reject validation requests', function (): void {
    $this->form->update(['publish_time' => now()->addDay()]);
    $this->withHeaders(['Precognition' => 'true', 'Precognition-Validate-Only' => "data.{$this->field->id}.value"])
        ->postJson(route('registrations.store', $this->form), ['data' => [$this->field->id => ['value' => 'valid']]])->assertForbidden();
});

test('validation requests have a separate rate limit from submissions', function (): void {
    $payload = ['data' => [$this->field->id => ['value' => 'valid']]];
    for ($i = 0; $i < 60; $i++) {
        $this->withHeaders(['Precognition' => 'true', 'Precognition-Validate-Only' => "data.{$this->field->id}.value"])
            ->postJson(route('registrations.store', $this->form), $payload)->assertNoContent();
    }
    $this->postJson(route('registrations.store', $this->form), $payload)->assertTooManyRequests();
    $this->flushHeaders()->postJson(route('registrations.store', $this->form), $payload)->assertRedirect();
});

test('model-backed options are checked against their source', function (string $model, bool $valid): void {
    $this->field->update(['type' => 'enum', 'use_model_options' => true, 'options_model' => $model]);
    $value = $valid ? $model::factory()->create()->getKey() : 'missing-option';
    $response = $this->postJson(route('registrations.store', $this->form), ['data' => [$this->field->id => ['value' => $value]]]);
    if ($valid) {
        $response->assertRedirect();
    } else {
        $response->assertUnprocessable()->assertJsonValidationErrors("data.{$this->field->id}.value");
    }
})->with([
    'tenant accepted' => [Tenant::class, true],
    'tenant rejected' => [Tenant::class, false],
    'institution accepted' => [Institution::class, true],
    'institution rejected' => [Institution::class, false],
]);

test('omitted optional responses do not create field responses', function (): void {
    $optional = FormField::factory()->for($this->form)->create(['type' => 'number', 'is_required' => false]);
    $this->postJson(route('registrations.store', $this->form), ['data' => [$this->field->id => ['value' => 'valid']]])->assertRedirect();
    expect(FieldResponse::query()->where('form_field_id', $optional->id)->exists())->toBeFalse();
});
