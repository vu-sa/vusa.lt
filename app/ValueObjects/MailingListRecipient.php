<?php

namespace App\ValueObjects;

final readonly class MailingListRecipient
{
    /**
     * @param  list<string>  $reasons  why the address is on the list, one line per matching source
     */
    public function __construct(
        public string $email,
        public ?string $displayName,
        public ?string $userId,
        public array $reasons,
        public bool $assignmentAddress = false,
    ) {}

    /**
     * @param  list<string>  $reasons
     */
    public function withReasons(array $reasons): self
    {
        return new self(
            $this->email,
            $this->displayName,
            $this->userId,
            array_values(array_unique([...$this->reasons, ...$reasons])),
            $this->assignmentAddress,
        );
    }
}
