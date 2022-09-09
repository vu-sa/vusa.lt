<?php

namespace App\Mail;

use App\Models\Registration;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ConfirmMemberRegistration extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * The order instance.
     *
     * @var \App\Models\Saziningai
     */

    public $registration;
    public $registerLocation;
    public $chairPerson;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($registration, $registerLocation, $chairPerson)
    {
        $this->registration = $registration;
        $this->registerLocation = $registerLocation;
        $this->chairPerson = $chairPerson;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject("📝 Sėkmingai užregistravote į " . $this->registerLocation)->replyTo($this->chairPerson->email)->markdown('emails.memberRegistration.confirm');
    }
}
