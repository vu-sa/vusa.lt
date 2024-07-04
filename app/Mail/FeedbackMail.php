<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class FeedbackMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public string $feedback;

    public $user;

    public string $href;

    public ?string $selectedText;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(string $feedback, $user, string $href = null, string $selectedText = null)
    {
        $this->feedback = $feedback;
        $this->user = $user;
        $this->href = $href;
        $this->selectedText = $selectedText;
    }

    /**
     * Get the message envelope.
     *
     * @return \Illuminate\Mail\Mailables\Envelope
     */
    public function envelope()
    {
        return new Envelope(
            subject: 'Feedback Mail',
        );
    }

    /**
     * Get the message content definition.
     *
     * @return \Illuminate\Mail\Mailables\Content
     */
    public function content()
    {
        return new Content(
            markdown: 'emails.feedback',
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array
     */
    public function attachments()
    {
        return [];
    }
}
