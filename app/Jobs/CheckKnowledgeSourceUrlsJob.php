<?php

namespace App\Jobs;

use App\Models\KnowledgeItemSource;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CheckKnowledgeSourceUrlsJob implements ShouldQueue
{
    use Queueable;

    public $tries = 1;

    public $timeout = 60;

    public function __construct(public int $spacingSeconds = 5) {}

    public function handle(): void
    {
        $sources = KnowledgeItemSource::query()->whereNull('document_id')->whereNotNull('url')->where('url', '!=', '')
            ->whereHas('item', fn ($query) => $query->active())->orderBy('id')->lazyById();
        foreach ($sources as $i => $source) {
            CheckKnowledgeSourceUrlJob::dispatch($source)->delay(now()->addSeconds($i * $this->spacingSeconds));
        }
    }
}
