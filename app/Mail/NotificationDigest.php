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

/**
 * Maximum number of notification items to show per category in the digest.
 */
const DIGEST_MAX_ITEMS_PER_CATEGORY = 3;

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
        // Transform category keys to translated labels, limit items, and track overflow
        $categorizedItems = [];
        $remainingCounts = [];
        $categoryColors = [];

        foreach ($this->groupedItems as $categoryValue => $items) {
            $category = NotificationCategory::tryFrom($categoryValue);
            $label = $category ? __($category->labelKey()) : $categoryValue;
            $color = $category ? $category->color() : 'gray';

            $itemsCollection = collect($items);
            $totalInCategory = $itemsCollection->count();

            // Limit to max items per category
            $categorizedItems[$label] = $itemsCollection->take(DIGEST_MAX_ITEMS_PER_CATEGORY)->all();

            // Track how many more items exist beyond the limit
            $remaining = $totalInCategory - DIGEST_MAX_ITEMS_PER_CATEGORY;
            $remainingCounts[$label] = $remaining > 0 ? $remaining : 0;

            // Store color for styling
            $categoryColors[$label] = $color;
        }

        return new Content(
            markdown: 'emails.notification-digest',
            with: [
                'user' => $this->user,
                'categorizedItems' => $categorizedItems,
                'remainingCounts' => $remainingCounts,
                'categoryColors' => $categoryColors,
                'totalCount' => collect($this->groupedItems)->flatten(1)->count(),
                'dashboardUrl' => route('dashboard.notifications'),
            ],
        );
    }
}
