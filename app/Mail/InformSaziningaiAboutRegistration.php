<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Saziningai;

class InformSaziningaiAboutRegistration extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * The order instance.
     *
     * @var \App\Models\Saziningai
     */
    public $saziningai;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($saziningai)
    {
        $this->saziningai = $saziningai;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Informacija apie užregistruotą atsiskaitymą (ID: ' . $this->saziningai->id . ')')->markdown('emails.saziningai.inform');
    }
}
