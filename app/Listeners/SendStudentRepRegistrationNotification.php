<?php

namespace App\Listeners;

use App\Actions\GetInstitutionManagers;
use App\Events\StudentRepRegistrationCreated;
use App\Helpers\AddressivizeHelper;
use App\Mail\ConfirmStudentRepRegistration;
use App\Models\FieldResponse;
use App\Notifications\StudentRepRegistrationNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

class SendStudentRepRegistrationNotification implements ShouldQueue
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
    public function handle(StudentRepRegistrationCreated $event): void
    {
        $form = $event->registration->form;
        $institution = $event->institution;

        $fieldResponses = FieldResponse::query()->with('formField')
            ->where('registration_id', $event->registration->id)->get();

        $emailResponse = null;
        $nameResponse = null;

        foreach ($fieldResponses as $fieldResponse) {
            $subtype = $fieldResponse->formField->subtype;

            if ($subtype === 'email' && $emailResponse === null) {
                $emailResponse = $fieldResponse;
            } elseif ($subtype === 'name' && $nameResponse === null) {
                $nameResponse = $fieldResponse;
            }

            if ($emailResponse !== null && $nameResponse !== null) {
                break;
            }
        }
        if (! $emailResponse || ! $nameResponse) {
            Log::error('Missing email or name field in student rep registration', [
                'registration_id' => $event->registration->id,
                'form_id' => $form->id,
            ]);

            return;
        }

        // Get institution managers
        $managers = GetInstitutionManagers::execute($institution);

        if ($managers->isEmpty()) {
            Log::warning('No institution managers found for student rep registration notification', [
                'event' => 'student_rep_registration_notification_no_managers',
                'institution_id' => $institution->id,
                'institution_name' => $institution->name,
                'registration_id' => $event->registration->id,
                'registration_email' => $emailResponse->getValue(),
                'registration_name' => $nameResponse->getValue(),
            ]);
        }

        // Get the first manager for the confirmation email reply-to
        $primaryManager = $managers->first();

        // Send confirmation email to registrant
        Mail::to($emailResponse->getValue())->send(new ConfirmStudentRepRegistration(
            AddressivizeHelper::addressivizeEveryWord($nameResponse->getValue()),
            $institution,
            $primaryManager
        ));

        // Send notifications to all institution managers
        foreach ($managers as $manager) {
            Notification::send($manager, new StudentRepRegistrationNotification(
                (string) $event->registration->id,
                $nameResponse->getValue(),
                $institution,
                $form->id
            ));
        }

        Log::info('Student rep registration notifications sent', [
            'registration_id' => $event->registration->id,
            'institution_id' => $institution->id,
            'managers_notified' => $managers->count(),
        ]);
    }
}
