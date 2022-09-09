<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\SaziningaiExam;
use App\Models\SaziningaiExamFlow;

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
        return $this->subject('Informacija apie užregistruotą atsiskaitymą (ID: ' . $this->saziningai->id . ')')->markdown('emails.saziningai.inform');
    }
}
