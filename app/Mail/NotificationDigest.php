<?php

namespace App\Mail;

use App\Enums\NotificationCategory;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NotificationDigest extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     *
     * @param  array<string, array<array{title: string, body: string, url: string, icon: string}>>  $groupedItems
     */
    public function __construct(
        public User $user,
        public array $groupedItems
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $totalCount = collect($this->groupedItems)->flatten(1)->count();

        return new Envelope(
            subject: '📬 '.trans_choice('notifications.digest_subject', $totalCount, ['count' => $totalCount]),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        // Transform category keys to translated labels
        $categorizedItems = [];
        foreach ($this->groupedItems as $categoryValue => $items) {
            $category = NotificationCategory::tryFrom($categoryValue);
            $label = $category ? __($category->labelKey()) : $categoryValue;
            $categorizedItems[$label] = $items;
        }

        return new Content(
            markdown: 'emails.notification-digest',
            with: [
                'user' => $this->user,
                'categorizedItems' => $categorizedItems,
                'totalCount' => collect($this->groupedItems)->flatten(1)->count(),
            ],
        );
    }
}
