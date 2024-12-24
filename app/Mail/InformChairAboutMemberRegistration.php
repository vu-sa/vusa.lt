<?php

namespace App\Mail;

use App\Models\Institution;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InformChairAboutMemberRegistration extends Mailable // implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(public string $registration_id, public string $name, public Institution $institution) {}

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject($this->name . ' užsiregistravo į ' . $this->institution->getMaybeShortNameAttribute())->markdown('emails.memberRegistration.inform');
    }
}
