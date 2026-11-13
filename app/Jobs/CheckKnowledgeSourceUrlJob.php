<?php

namespace App\Jobs;

use App\Models\KnowledgeItemSource;
use App\Services\Learning\SourceUrlChecker;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CheckKnowledgeSourceUrlJob implements ShouldQueue
{
    use Queueable;

    public $tries = 3;

    public $timeout = 90;

    public bool $deleteWhenMissingModels = true;

    public function __construct(public KnowledgeItemSource $source) {}

    public function handle(SourceUrlChecker $checker): void
    {
        $checker->check($this->source);
    }
}
