<?php

namespace App\Mail;

use App\Models\SaziningaiExam;
use App\Models\SaziningaiExamFlow;
use App\Models\SaziningaiExamObserver;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InformSaziningaiAboutObserverRegistration extends Mailable
{
    use Queueable, SerializesModels;

    public $saziningai_people;
    public $saziningaiFlow;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(SaziningaiExamObserver $saziningai_people, SaziningaiExamFlow $saziningaiFlow)
    {
        $this->saziningai_people = $saziningai_people;
        $this->saziningaiFlow = $saziningaiFlow;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Stebėtojas užsiregistravo į atsiskaitymą (ID: ' . $this->saziningaiFlow->exam->id . ')')->markdown('emails.saziningai_people.inform');
    }
}
