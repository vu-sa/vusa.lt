<?php

use App\Models\Duty;
use App\Models\Form;
use App\Models\FormField;
use App\Models\Institution;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Settings\FormSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Symfony\Component\Mailer\SentMessage;

pest()->use(RefreshDatabase::class);

beforeEach(function (): void {
    $this->tenant = Tenant::factory()->create();
    $this->institution = Institution::factory()->for($this->tenant)->create();
    $this->tenant->primary_institution_id = $this->institution->id;
    $this->tenant->save();

    $this->role = Role::firstOrCreate(['name' => 'Member Registration Coordinator', 'guard_name' => 'web']);

    $this->form = createMemberRegistrationForm($this->tenant);

    $settings = app(FormSettings::class);
    $settings->member_registration_form_id = $this->form->id;
    $settings->member_registration_notification_recipient_role_id = $this->role->id;
    $settings->save();
    app()->forgetInstance(FormSettings::class);
});

// Mail::fake() cannot see this mailable: the notification's toMail() returns a
// Mailable and the notification channel calls $mailable->send(), which hands
// MailFake the rendered view array — the fake only records Mailable arguments.
// The array transport holds the real envelopes instead.
function sentMail(): Collection
{
    $transport = app('mail.manager')->mailer('array')->getSymfonyTransport();

    return collect($transport->messages())->map(fn (SentMessage $sent) => [
        'recipients' => collect($sent->getOriginalMessage()->getTo())->map(fn ($address) => $address->getAddress())->all(),
        'subject' => $sent->getOriginalMessage()->getSubject(),
    ]);
}

function informChairMail(): array
{
    return sentMail()->first(fn (array $mail) => str_contains($mail['subject'], 'užsiregistravo'));
}

describe('coordinator mail recipient', function (): void {
    test('mailable is created for the duty email, not the duty holder personal email', function (): void {
        $user = User::factory()->create(['email' => 'personal@gmail.com']);
        $duty = Duty::factory()
            ->for($this->institution)
            ->hasAttached($user, ['start_date' => now()->subDay(), 'end_date' => now()->addYear(), 'additional_email' => null])
            ->create(['name' => 'Naujų narių koordinatorius', 'email' => 'koordinatorius@mif.vusa.lt']);
        $duty->assignRole($this->role);

        submitMemberRegistration($this->form, $this->tenant);

        expect($duty->current_users()->first()->is($user))->toBeTrue();

        $mail = informChairMail();
        expect($mail)->not->toBeNull()
            ->and($mail['recipients'])->toBe(['koordinatorius@mif.vusa.lt'])
            ->and($mail['recipients'])->not->toContain('personal@gmail.com');

        // The registrant confirmation must be the only other message.
        expect(sentMail())->toHaveCount(2);
    });

    test('user holding more duties without additional emails still gets mail only to the role duty email', function (): void {
        $user = User::factory()->create(['email' => 'personal@gmail.com']);
        $pivot = ['start_date' => now()->subDay(), 'end_date' => now()->addYear(), 'additional_email' => null];

        $coordinatorDuty = Duty::factory()
            ->for($this->institution)
            ->hasAttached($user, $pivot)
            ->create(['name' => 'Naujų narių koordinatorius', 'email' => 'koordinatorius@mif.vusa.lt']);
        $coordinatorDuty->assignRole($this->role);

        // Attached first on purpose: User::routeNotificationForMail() (via
        // NotificationRouter) returns the first current duty with a @vusa.lt
        // address, so if delivery ever routed through it, this address would win.
        Duty::factory()
            ->for($this->institution)
            ->hasAttached($user, $pivot)
            ->create(['name' => 'Pirmininkas', 'email' => 'pirmininkas@vusa.lt']);

        Duty::factory()
            ->for($this->institution)
            ->hasAttached($user, $pivot)
            ->create(['name' => 'Narys', 'email' => null]);

        submitMemberRegistration($this->form, $this->tenant);

        $mail = informChairMail();
        expect($mail)->not->toBeNull()
            ->and($mail['recipients'])->toBe(['koordinatorius@mif.vusa.lt'])
            ->and($mail['recipients'])->not->toContain('pirmininkas@vusa.lt')
            ->and($mail['recipients'])->not->toContain('personal@gmail.com');
    });
});

function createMemberRegistrationForm(Tenant $tenant): Form
{
    $form = Form::factory()->published()->create(['tenant_id' => $tenant->id]);

    FormField::factory()->create([
        'form_id' => $form->id,
        'type' => 'string',
        'subtype' => 'name',
        'label' => ['lt' => 'Vardas, pavardė', 'en' => 'Name'],
        'is_required' => true,
        'order' => 1,
    ]);

    FormField::factory()->create([
        'form_id' => $form->id,
        'type' => 'string',
        'subtype' => 'email',
        'label' => ['lt' => 'El. paštas', 'en' => 'Email'],
        'is_required' => true,
        'order' => 2,
    ]);

    FormField::factory()->create([
        'form_id' => $form->id,
        'type' => 'enum',
        'label' => ['lt' => 'Padalinys', 'en' => 'Unit'],
        'is_required' => true,
        'order' => 3,
        'use_model_options' => true,
        'options_model' => Tenant::class,
    ]);

    return $form->fresh(['formFields']);
}

function submitMemberRegistration(Form $form, Tenant $tenant): void
{
    $fields = $form->formFields;
    $nameField = $fields->firstWhere('subtype', 'name');
    $emailField = $fields->firstWhere('subtype', 'email');
    $tenantField = $fields->first(fn ($f) => $f->use_model_options && $f->options_model === Tenant::class);

    test()->post(route('registrations.store', $form), [
        'data' => [
            $nameField->id => ['value' => 'Naujas Narys'],
            $emailField->id => ['value' => 'naujas.narys@example.com'],
            $tenantField->id => ['value' => (string) $tenant->id],
        ],
    ])->assertRedirect();
}
