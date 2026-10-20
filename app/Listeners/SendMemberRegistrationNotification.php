<?php

namespace App\Listeners;

use App\Enums\ResponsibilityPurpose;
use App\Events\MemberRegistrationCreated;
use App\Helpers\AddressivizeHelper;
use App\Mail\ConfirmMemberRegistration;
use App\Models\Duty;
use App\Models\FieldResponse;
use App\Models\Institution;
use App\Models\Tenant;
use App\Notifications\MemberRegistrationNotification;
use App\Services\ResponsibilityResolver;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

class SendMemberRegistrationNotification implements ShouldQueue
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(MemberRegistrationCreated $event): void
    {
        $form = $event->registration->form;

        // 2. Find if form has field with use_model_options == true and options_model Tenant
        $tenantField = $form->formFields->first(fn ($field) => $field->use_model_options && $field->options_model === Tenant::class);

        if (! $tenantField) {
            report('Tenant field not found in member registration form!');

            return;
        }

        $fieldResponses = FieldResponse::query()->with('formField')
            ->where('registration_id', $event->registration->id)->get();

        // find email field in field response (where form field subtype is email)
        $emailResponse = $fieldResponses->first(fn ($fieldResponse) => $fieldResponse->formField->subtype === 'email');

        $nameResponse = $fieldResponses->first(fn ($fieldResponse) => $fieldResponse->formField->subtype === 'name');

        $tenantResponse = $fieldResponses->first(fn ($fieldResponse) => $fieldResponse->formField->id === $tenantField->id);

        // Finalize variables
        $tenant = Tenant::query()->find($tenantResponse->getValue());
        if (! $tenant) {
            report('Tenant not found for member registration!');

            return;
        }

        /** @var Institution|null $institution */
        $institution = $tenant->primary_institution;
        if (! $institution) {
            report('Primary institution not found for tenant!');

            return;
        }

        $mailableDuties = app(ResponsibilityResolver::class)->dutiesForTenant(ResponsibilityPurpose::MemberRegistration, $tenant);

        /** @var Duty|null $dutyContact */
        $dutyContact = $mailableDuties->first();

        if ($dutyContact === null) {
            Log::warning('No duty holds the member registration responsibility for this padalinys', [
                'event' => 'member_registration_notification_failed',
                'tenant_id' => $tenant->id,
                'institution_id' => $institution->id,
                'registration_id' => $event->registration->id,
            ]);

            // Skip sending the email completely when no appropriate duty is found
            return;
        }

        Mail::to($emailResponse->getValue())->send(new ConfirmMemberRegistration(
            AddressivizeHelper::addressivizeEveryWord($nameResponse->getValue()),
            $institution,
            $dutyContact
        ));

        foreach ($mailableDuties as $mailableDuty) {
            /** @var Duty $mailableDuty */
            $notification = new MemberRegistrationNotification($event->registration->id, $nameResponse->getValue(), $institution, $mailableDuty->email, $form->id);
            $holder = $mailableDuty->current_users()->first();

            // A vacant duty still has an inbox; only the in-app notice needs a person.
            if ($holder === null) {
                Mail::send($notification->toMail($mailableDuty));

                continue;
            }

            Notification::send($holder, $notification);
        }
    }
}
