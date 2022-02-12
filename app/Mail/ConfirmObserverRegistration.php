<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ConfirmObserverRegistration extends Mailable
{
    use Queueable, SerializesModels;

    public $saziningai_people;
    public $saziningai;
    
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($saziningai_people, $saziningai)
    {
        $this->saziningai_people = $saziningai_people;
        $this->saziningai = $saziningai;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject("Sėkmingai užsiregistravote atsiskaitymo stebėjimui 🧑‍🎓")->markdown('emails.saziningai.confirmObserverRegistration');
    }
}
