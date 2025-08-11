<?php

namespace App\Listeners;

use App\Events\MemberRegistrationCreated;
use App\Helpers\AddressivizeHelper;
use App\Mail\ConfirmMemberRegistration;
use App\Models\FieldResponse;
use App\Models\Institution;
use App\Models\Tenant;
use App\Notifications\MemberRegistered;
use App\Settings\FormSettings;
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
        $tenantField = $form->formFields->first(function ($field) {
            return $field->use_model_options && $field->options_model === Tenant::class;
        });

        if (! $tenantField) {
            report('Tenant field not found in member registration form!');

            return;
        }

        $fieldResponses = FieldResponse::query()->with('formField')
            ->where('registration_id', $event->registration->id)->get();

        // find email field in field response (where form field subtype is email)
        $emailResponse = $fieldResponses->first(function ($fieldResponse) {
            return $fieldResponse->formField->subtype === 'email';
        });

        $nameResponse = $fieldResponses->first(function ($fieldResponse) {
            return $fieldResponse->formField->subtype === 'name';
        });

        $tenantResponse = $fieldResponses->first(function ($fieldResponse) use ($tenantField) {
            return $fieldResponse->formField->id === $tenantField->id;
        });

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

        // Find duty for tenant which has a role set in FormSettings::class
        $mailableDuties = $institution->duties()->whereHas('roles', function ($query) {
            $query->where('id', app(FormSettings::class)->member_registration_notification_recipient_role_id);
        })->get();

        /** @var \App\Models\Duty|null $dutyContact */
        $dutyContact = $mailableDuties->first();

        if ($dutyContact === null) {
            // Log warning that will appear in Telescope
            Log::warning('No duty found with configured role for member registration notification', [
                'event' => 'member_registration_notification_failed',
                'institution_id' => $institution->id,
                'institution_name' => $institution->name,
                'configured_role_id' => app(FormSettings::class)->member_registration_notification_recipient_role_id,
                'registration_id' => $event->registration->id,
                'registration_email' => $emailResponse->getValue(),
                'registration_name' => $nameResponse->getValue(),
                'available_duties_count' => $institution->duties()->count(),
                'total_roles_in_institution' => $institution->duties()->with('roles')->get()->pluck('roles')->flatten()->unique('id')->count(),
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
            /** @var \App\Models\Duty $mailableDuty */
            Notification::send($mailableDuty->current_users()->first(), new MemberRegistered($event->registration->id, $nameResponse->getValue(), $institution, $mailableDuty->email));
        }
    }
}
