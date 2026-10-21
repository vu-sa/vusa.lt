<?php

namespace App\Jobs;

use App\Services\InstitutionStatusSnapshotService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ReconstructInstitutionStatusHistory implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $uniqueFor = 600;

    public int $timeout = 300;

    public int $tries = 3;

    public function __construct(public string $from, public string $to) {}

    public function uniqueId(): string
    {
        return $this->from.':'.$this->to;
    }

    public function handle(InstitutionStatusSnapshotService $snapshots): void
    {
        $snapshots->capture(CarbonImmutable::parse($this->from), CarbonImmutable::parse($this->to), reconstructed: true);
    }
}
