<?php

namespace App\Services\Documents;

/**
 * What one discovery run did (or, in a dry run, would do).
 */
class DiscoveryResult
{
    public int $created = 0;

    public int $updated = 0;

    public int $removed = 0;

    public int $restored = 0;

    public int $failed = 0;

    /** Failed items that no longer hold the checkpoint back, after failing several runs in a row. */
    public int $givenUp = 0;

    /** Documents whose padalinys label now matches an institution, though the file did not change. */
    public int $rematched = 0;

    /** @var array<string, true> Padalinys labels that matched no institution */
    public array $unmatchedLabels = [];

    /**
     * @param  'already_running'|'mass_removal'|null  $abortReason
     */
    public function __construct(
        public bool $full = false,
        public bool $dryRun = false,
        public ?string $abortReason = null,
        /** The first run ever: new files were filed as hidden, not pending. */
        public bool $baseline = false,
        /** A sample (`--limit`): nothing removed, change-feed position not saved. */
        public bool $limited = false,
    ) {}

    public static function aborted(string $reason, bool $full, int $wouldRemove = 0): self
    {
        $result = new self(full: $full, abortReason: $reason);
        $result->removed = $wouldRemove;

        return $result;
    }

    public function wasAborted(): bool
    {
        return $this->abortReason !== null;
    }

    /**
     * @return array{created: int, updated: int, removed: int, restored: int, failed: int, given_up: int, rematched: int, unmatched_labels: list<string>, full: bool, dry_run: bool, baseline: bool, limited: bool, abort_reason: ?string}
     */
    public function toArray(): array
    {
        return [
            'created' => $this->created,
            'updated' => $this->updated,
            'removed' => $this->removed,
            'restored' => $this->restored,
            'failed' => $this->failed,
            'given_up' => $this->givenUp,
            'rematched' => $this->rematched,
            'unmatched_labels' => array_keys($this->unmatchedLabels),
            'full' => $this->full,
            'dry_run' => $this->dryRun,
            'baseline' => $this->baseline,
            'limited' => $this->limited,
            'abort_reason' => $this->abortReason,
        ];
    }
}
