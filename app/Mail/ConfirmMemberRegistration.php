<?php

namespace App\Mail;

use App\Helpers\GenitivizeHelper;
use App\Models\Duty;
use App\Models\Institution;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ConfirmMemberRegistration extends Mailable
{
    use Queueable, SerializesModels;

    public string $contactName;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(public string $name, public Institution $institution, public Duty $dutyContact)
    {
        /** @var User|null $user */
        $user = $dutyContact->current_users()->first();

        $this->contactName = $user?->name ? GenitivizeHelper::genitivizeEveryWord($user->name) : $dutyContact->email;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('📝 '.__('mail.confirm.title', ['institution' => $this->institution->getMaybeShortNameAttribute()]))
            ->replyTo($this->dutyContact->email)
            ->markdown('emails.memberRegistration.confirm');
    }
}
