<?php

namespace App\Console\Commands;

use App\Actions\GenerateUniqueSlug;
use App\Models\News;
use App\Models\Page;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

/**
 * Rewrites a News/Page permalink to what `Str::slug($title)` (via `GenerateUniqueSlug`) would
 * produce today, but only when the stored permalink doesn't already match it — a record whose
 * permalink is already current formula is left untouched, never resynced speculatively.
 *
 * Applying a change is a plain `$model->permalink = $new; $model->save();` — nothing else to do
 * manually: the model's own `saved` hook (Calendar/News/Page::booted()) detects the permalink
 * changed and writes the old URL to public_urls as a legacy redirect on its own.
 */
#[Description("Rewrite News/Page permalinks that don't match today's Str::slug(title) formula, demoting the old permalink to a legacy redirect.")]
#[Signature('permalinks:normalize
                            {model? : Model type to normalize (news, page) — omit for both}
                            {--dry-run : Report proposed changes without writing}
                            {--force : Skip the confirmation prompt}')]
class NormalizePermalinks extends Command
{
    private const array TARGETS = ['news', 'page'];

    public function handle(): int
    {
        $model = $this->argument('model');
        $dryRun = (bool) $this->option('dry-run');

        if ($model !== null && ! in_array($model, self::TARGETS, true)) {
            $this->error("Unknown model type: {$model}");
            $this->line('Available types: '.implode(', ', self::TARGETS));

            return self::FAILURE;
        }

        $targets = $model !== null ? [$model] : self::TARGETS;

        foreach ($targets as $type) {
            $this->info("Scanning {$type}...");
            $proposed = $this->proposedChanges($type);

            if ($proposed === []) {
                $this->line('  Nothing to change — every permalink already matches the current formula.');

                continue;
            }

            foreach ($proposed as [, $id, $old, $new]) {
                $this->line("  #{$id}: {$old} → {$new}");
            }

            if ($dryRun) {
                continue;
            }

            if (! $this->option('force') && ! $this->confirm(sprintf('Apply %d %s permalink change(s)?', count($proposed), $type))) {
                $this->info('Skipped.');

                continue;
            }

            $this->apply($type, $proposed);
        }

        return self::SUCCESS;
    }

    /**
     * @return array<int, array{0: class-string<News>|class-string<Page>, 1: int, 2: string, 3: string}>
     */
    private function proposedChanges(string $type): array
    {
        $modelClass = $type === 'news' ? News::class : Page::class;
        $changes = [];

        /** @var Builder<News>|Builder<Page> $query */
        $query = $modelClass::query();

        $query->chunkById(200, function ($chunk) use ($modelClass, &$changes): void {
            foreach ($chunk as $model) {
                if (blank($model->title) || blank($model->tenant_id)) {
                    continue;
                }

                $newSlug = GenerateUniqueSlug::execute($modelClass, $model->title, $model->tenant_id, $model->id);

                if ($newSlug === $model->permalink) {
                    continue;
                }

                $changes[] = [$modelClass, $model->id, (string) $model->permalink, $newSlug];
            }
        });

        return $changes;
    }

    /**
     * Re-resolves each slug immediately before saving rather than trusting the value shown
     * during the (possibly much earlier) review step: two records proposed in the same scan can
     * target the same slug on paper (neither sees the other's *pending* change), which would
     * violate the permalink+tenant unique index if applied as planned. Recomputing here against
     * the live DB — already updated by every save earlier in this same loop — is self-correcting.
     *
     * @param  array<int, array{0: class-string<News>|class-string<Page>, 1: int, 2: string, 3: string}>  $proposed
     */
    private function apply(string $type, array $proposed): void
    {
        $modelClass = $type === 'news' ? News::class : Page::class;
        $changed = 0;

        foreach ($proposed as [, $id]) {
            $model = $modelClass::find($id);

            if ($model === null || blank($model->title) || blank($model->tenant_id)) {
                continue;
            }

            $newSlug = GenerateUniqueSlug::execute($modelClass, $model->title, $model->tenant_id, $model->id);

            if ($newSlug === $model->permalink) {
                continue;
            }

            $model->permalink = $newSlug;
            $model->save();
            $changed++;
        }

        $this->info("  Changed {$changed} {$type} permalink(s).");
    }
}
