<?php

namespace App\Support\Dutiables;

/**
 * One problem found in a set of dutiable rows.
 *
 * Deliberately data only: the label, the severity wording and the fix button all live in
 * the frontend, keyed by `code`, so a new check needs no backend copy.
 */
final readonly class DutiableDiagnostic
{
    public const string SEVERITY_ERROR = 'error';

    public const string SEVERITY_WARNING = 'warning';

    public const string SEVERITY_INFO = 'info';

    /**
     * @param  list<string>  $rowIds  the rows this finding is about; a pair for `overlap`
     * @param  array<string, mixed>  $detail  everything the suggested fix needs
     */
    public function __construct(
        public string $code,
        public string $severity,
        public array $rowIds,
        public ?string $dutyId = null,
        public array $detail = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'severity' => $this->severity,
            'row_ids' => $this->rowIds,
            'duty_id' => $this->dutyId,
            'detail' => $this->detail,
        ];
    }
}
