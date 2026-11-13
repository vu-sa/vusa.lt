<?php

namespace App\Console\Commands;

use App\Models\KnowledgeItemSource;
use App\Services\Learning\SourceUrlChecker;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Description('Check knowledge item source URLs and refresh their previews')]
#[Signature('knowledge:check-source-urls {--item= : Only this knowledge item}')]
class CheckKnowledgeSourceUrlsCommand extends Command
{
    public function handle(SourceUrlChecker $checker): int
    {
        $sources = KnowledgeItemSource::query()->whereNull('document_id')->whereNotNull('url')->where('url', '!=', '')
            ->whereHas('item', fn ($query) => $query->active())
            ->when($this->option('item'), fn ($query, $item) => $query->where('knowledge_item_id', $item))->orderBy('id')->get();
        $bar = $this->output->createProgressBar($sources->count());
        $bar->start();
        foreach ($sources as $source) {
            $checker->check($source);
            $this->line(" {$source->id}: {$source->url} – {$source->check_status}", verbosity: 'v');
            $bar->advance();
        }
        $bar->finish();
        $this->newLine();
        $broken = $sources->filter(fn (KnowledgeItemSource $source): bool => $source->check_failures > 0)->count();
        $previews = $sources->filter(fn (KnowledgeItemSource $source): bool => $source->thumbnail_path !== null)->count();
        $this->info("Checked {$sources->count()} links, {$broken} not working, {$previews} previews.");

        return self::SUCCESS;
    }
}
