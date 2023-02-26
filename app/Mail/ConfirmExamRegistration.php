<?php

namespace App\Mail;

use App\Models\SaziningaiExam;
use App\Models\SaziningaiExamFlow;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ConfirmExamRegistration extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * The order instance.
     *
     * @var \App\Models\Saziningai
     */
    public $saziningai;

    public $saziningaiFlow;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(SaziningaiExam $saziningai, SaziningaiExamFlow $saziningaiFlow)
    {
        $this->saziningai = $saziningai;
        $this->saziningaiFlow = $saziningaiFlow;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('📝 Sėkmingai užregistravote atsiskaitymą VU SA sistemoje')->replyTo('saziningai@vusa.lt')->markdown('emails.saziningai.confirmRegistration');
    }
}
