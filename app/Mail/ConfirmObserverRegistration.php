<?php

namespace App\Mail;

use App\Models\SaziningaiExam;
use App\Models\SaziningaiExamFlow;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ConfirmObserverRegistration extends Mailable
{
    use Queueable, SerializesModels;

    public $saziningaiFlow;
    
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(SaziningaiExamFlow $saziningaiFlow)
    {
        $this->saziningaiFlow = $saziningaiFlow;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject("🧑‍🎓 Sėkmingai užsiregistravote atsiskaitymo stebėjimui")->replyTo('saziningai@vusa.lt')->markdown('emails.saziningai.confirmObserverRegistration');
    }
}
