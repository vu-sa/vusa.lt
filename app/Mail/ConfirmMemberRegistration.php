<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ConfirmMemberRegistration extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * The order instance.
     *
     * @var \App\Models\Registration
     */
    public $registration;

    public $registerLocation;

    public $chairPerson;

    public $chairEmail;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($registration, $registerLocation, $chairPerson, $chairEmail)
    {
        $this->registration = $registration;
        $this->registerLocation = $registerLocation;
        $this->chairPerson = $chairPerson;
        $this->chairEmail = $chairEmail;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('📝 '.__('mail.confirmRegistrationTitle').' '.$this->registerLocation)
            ->replyTo($this->chairEmail)
            ->markdown('emails.memberRegistration.confirm');
    }
}
