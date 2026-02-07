<?php

namespace App\Mail;

use App\Helpers\GenitivizeHelper;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ConfirmStudentRepRegistration extends Mailable
{
    use Queueable, SerializesModels;

    public string $contactName;

    public ?string $contactEmail;

    /**
     * Create a new message instance.
     *
     * @param  string  $name  The registrant's name (addressivized)
     * @param  Institution  $institution  The institution they want to represent
     * @param  User|null  $manager  The institution manager to contact
     */
    public function __construct(
        public string $name,
        public Institution $institution,
        public ?User $manager = null
    ) {
        $this->contactName = $manager?->name
            ? GenitivizeHelper::genitivizeEveryWord($manager->name)
            : __('mail.student_rep.default_contact');
        $this->contactEmail = $manager?->email;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $mail = $this->subject('📝 '.__('mail.student_rep.confirm.title', ['institution' => $this->institution->getMaybeShortNameAttribute()]))
            ->markdown('emails.studentRepRegistration.confirm');

        if ($this->contactEmail) {
            $mail->replyTo($this->contactEmail);
        }

        return $mail;
    }
}
