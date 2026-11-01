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
use Illuminate\Support\Str;

class NotificationDigest extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    private const int MAX_LINES_PER_CATEGORY = 5;

    private const array CATEGORY_ORDER = [
        NotificationCategory::Task, NotificationCategory::Meeting, NotificationCategory::Reservation,
        NotificationCategory::Comment, NotificationCategory::Duty, NotificationCategory::Registration,
        NotificationCategory::User, NotificationCategory::System,
    ];

    /** @param array<string, array<int, array<string, mixed>>> $groupedItems */
    public function __construct(
        public User $user,
        public array $groupedItems
    ) {}

    public function envelope(): Envelope
    {
        $items = collect($this->groupedItems)->flatten(1);
        $labels = collect($this->orderedItems())->keys()->map(function (string $value): string {
            $category = NotificationCategory::tryFrom($value);

            return Str::lower($category ? __($category->labelKey()) : $value);
        })->implode(', ');
        $subject = $items->count() === 1
            ? $items->first()['title']
            : trans_choice('notifications.digest_subject_categories', $items->count(), [
                'count' => $items->count(), 'categories' => $labels,
            ]);

        return new Envelope(subject: Str::limit($subject, 59, '…', preserveWords: true));
    }

    public function content(): Content
    {
        $categorizedItems = [];
        $remainingCounts = [];
        $categoryColors = [];

        foreach ($this->orderedItems() as $categoryValue => $items) {
            $category = NotificationCategory::tryFrom($categoryValue);
            $label = $category ? __($category->labelKey()) : $categoryValue;
            $color = $category ? $category->colorHex() : '#58554f';

            $categorizedItems[$label] = $this->sections($items);
            $remainingCounts[$label] = max(0, count($items) - self::MAX_LINES_PER_CATEGORY);
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
                'dashboardUrl' => route('notifications.index'),
                'settingsUrl' => route('profile.notifications'),
            ],
        );
    }

    private function orderedItems(): array
    {
        $ordered = [];
        foreach (self::CATEGORY_ORDER as $category) {
            if (array_key_exists($category->value, $this->groupedItems)) {
                $ordered[$category->value] = $this->groupedItems[$category->value];
            }
        }

        return $ordered + $this->groupedItems;
    }

    /** @param array<int, array<string, mixed>> $items */
    private function sections(array $items): array
    {
        $groups = [];
        foreach ($items as $index => $item) {
            $key = isset($item['type']) && ! array_key_exists('activity_questions', $item)
                ? 'type:'.$item['type'] : 'item:'.$index;
            $groups[$key][] = $item;
        }

        $sections = [];
        $remaining = self::MAX_LINES_PER_CATEGORY;
        foreach ($groups as $group) {
            if ($remaining === 0) {
                break;
            }
            $visible = array_slice($group, 0, $remaining);
            $sections[] = [
                'title' => $group[0]['digestGroupTitle'] ?? $group[0]['title'],
                'count' => count($group),
                'items' => $visible,
            ];
            $remaining -= count($visible);
        }

        return $sections;
    }
}
