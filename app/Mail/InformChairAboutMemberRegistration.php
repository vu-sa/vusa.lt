<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InformChairAboutMemberRegistration extends Mailable // implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * The order instance.
     *
     * @var \App\Models\Saziningai
     */
    public $registration;

    public $registerLocation;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($registration, $registerLocation)
    {
        $this->registration = $registration;
        $this->registerLocation = $registerLocation;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Informacija apie užregistravusį žmogų ('.$this->registration['name'].')')->markdown('emails.memberRegistration.inform');
    }
}
