<?php

namespace App\Mail;

use App\Models\Institution;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InformManagerAboutStudentRepRegistration extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public string $registration_id,
        public string $name,
        public Institution $institution,
        public string $form_id
    ) {}

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject(__('mail.student_rep.inform.subject', [
            'name' => $this->name,
            'institution' => $this->institution->getMaybeShortNameAttribute(),
        ]))->markdown('emails.studentRepRegistration.inform');
    }
}
