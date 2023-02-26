<?php

namespace App\Mail;

use App\Models\SaziningaiExam;
use App\Models\SaziningaiExamFlow;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InformSaziningaiAboutRegistration extends Mailable // implements ShouldQueue
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
        return $this->subject('Informacija apie užregistruotą atsiskaitymą (ID: '.$this->saziningai->id.')')->markdown('emails.saziningai.inform');
    }
}
