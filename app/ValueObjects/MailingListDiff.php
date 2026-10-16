<?php

namespace App\ValueObjects;

final readonly class MailingListDiff
{
    /** Above this share of the roster, removals wait for a person to apply them. */
    private const float REVIEW_REMOVAL_SHARE = 0.5;

    /**
     * @param  list<MailingListRecipient>  $toAdd
     * @param  list<array{email: string, display_name: string|null, member_id: string}>  $toRemove
     * @param  list<MailingListRecipient>  $desired
     */
    public function __construct(
        public array $toAdd,
        public array $toRemove,
        public array $desired,
        public int $currentCount,
    ) {}

    public function isEmpty(): bool
    {
        return $this->toAdd === [] && $this->toRemove === [];
    }

    /** A resolver bug or wrong source must not silently empty a long-standing list. */
    public function needsReview(): bool
    {
        if ($this->toRemove === []) {
            return false;
        }

        return $this->desired === []
            || count($this->toRemove) > $this->currentCount * self::REVIEW_REMOVAL_SHARE;
    }

    /**
     * @return array{add: list<array{email: string, display_name: string|null, reasons: list<string>}>, remove: list<array{email: string, display_name: string|null}>, desired_count: int, current_count: int, needs_review: bool}
     */
    public function toArray(): array
    {
        return [
            'add' => array_map(fn (MailingListRecipient $r) => [
                'email' => $r->email,
                'display_name' => $r->displayName,
                'reasons' => $r->reasons,
            ], $this->toAdd),
            'remove' => array_map(fn (array $m) => [
                'email' => $m['email'],
                'display_name' => $m['display_name'],
            ], $this->toRemove),
            'desired_count' => count($this->desired),
            'current_count' => $this->currentCount,
            'needs_review' => $this->needsReview(),
        ];
    }
}
