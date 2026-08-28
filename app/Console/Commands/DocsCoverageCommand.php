<?php

namespace App\Console\Commands;

use App\Support\Docs\ChangedTestAnalyzer;
use App\Support\Docs\ChangedTestFile;
use App\Support\Docs\DocClaims;
use App\Support\Docs\DocClaimScanner;
use App\Support\Docs\DocFreshness;
use App\Support\Docs\DocFreshnessAnalyzer;
use App\Support\Docs\FeatureArea;
use App\Support\Docs\FeatureSurface;
use App\Support\Docs\FeatureSurfaceScanner;
use App\Support\Docs\TestSurface;
use App\Support\Docs\TestSurfaceScanner;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Reports how well the app's feature surface is documented, keyed on the areas
 * an admin recognises rather than raw route names.
 *
 * Two axes, deliberately one-directional. A feature area with real tested
 * behaviour and no page is a writing task; a page whose cited tests have moved
 * on since it was reviewed has probably drifted. Neither is a false positive:
 * the report never claims a page is complete, only that evidence exists and is
 * no older than the last review.
 */
#[Description('Report how well the app\'s feature surface is documented')]
#[Signature('docs:coverage
        {--area= : Only report feature areas whose slug starts with this prefix}
        {--strict : Exit non-zero if a doc page claims a test file that does not exist}
        {--changed= : Review mode — report doc pages affected by test changes since this git ref}
        {--summary : Append a Markdown report to $GITHUB_STEP_SUMMARY}
        {--dashboard : Write the standing coverage dashboard to a file}
        {--dashboard-path= : Where the dashboard is written (default docs/maintainers/coverage.md)}
        {--docs-path= : Directory to scan for doc pages (default docs/) — mainly for tests}')]
class DocsCoverageCommand extends Command
{
    public function handle(
        TestSurfaceScanner $scanner,
        DocClaimScanner $docScanner,
        FeatureSurfaceScanner $featureScanner,
        DocFreshnessAnalyzer $freshnessAnalyzer,
    ): int {
        $docsPath = $this->option('docs-path') ?: base_path('docs');

        $surface = $scanner->scan(base_path('tests'));
        $claims = $docScanner->scan($docsPath);

        if ($base = $this->option('changed')) {
            return $this->reviewChanges($base, $claims);
        }

        $features = $featureScanner->scan($surface, $claims);
        $freshness = $freshnessAnalyzer->analyze($claims);

        $this->renderReport($features, $surface);
        $this->renderDocs($features, $claims);
        $this->renderFreshness($freshness);
        $this->renderWarnings($docScanner->warnings(), $freshnessAnalyzer->warnings());

        if ($this->option('summary')) {
            $this->writeStepSummary($features, $freshness);
        }

        if ($this->option('dashboard')) {
            $this->writeDashboard($features, $freshness);
        }

        $dangling = $claims->danglingClaims();

        if ($dangling !== [] && $this->option('strict')) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    // ---- default report -----------------------------------------------------

    private function renderReport(FeatureSurface $features, TestSurface $surface): void
    {
        $areas = $this->inScope($features);
        $areaCount = count($areas);
        $documented = count(array_filter($areas, fn (FeatureArea $a) => $a->isDocumented()));
        $withHelp = count(array_filter($areas, fn (FeatureArea $a) => $a->hasHelp));

        $this->newLine();
        $this->line(sprintf('  <options=bold>Feature areas</>  %d areas', $areaCount));
        $this->line(sprintf('    documented  %s  <fg=gray>%d%%</>', $this->bar($documented, $areaCount), $this->pct($documented, $areaCount)));
        $this->line(sprintf('    with help   %s  <fg=gray>%d%%</>', $this->bar($withHelp, $areaCount), $this->pct($withHelp, $areaCount)));
        // Test coverage of routes is not the headline — this is a docs tool. It
        // survives only as a per-area ranking hint in the backlog below.
        $this->line("  <fg=gray>scanned {$surface->fileCount} test files, {$surface->testCount} tests</>");
        $this->newLine();

        // Under --area, list the routes so the drill-down is legible; the full
        // surface is too noisy to dump unfiltered.
        if ($this->option('area')) {
            $this->renderAreaRoutes($areas, $surface);
        }
    }

    /**
     * @param  list<FeatureArea>  $areas
     */
    private function renderAreaRoutes(array $areas, TestSurface $surface): void
    {
        foreach ($areas as $area) {
            $this->line(sprintf('  <fg=yellow>%s</> <fg=gray>%s</>', $area->slug,
                $area->modelClass !== null ? class_basename($area->modelClass) : 'no model'));

            foreach ($area->routes as $route) {
                $mark = isset($surface->routes[$route]) ? '<fg=green>tested</>' : '<fg=red>untested</>';
                $this->line("      {$route}  {$mark}");
            }

            $this->newLine();
        }
    }

    private function renderDocs(FeatureSurface $features, DocClaims $claims): void
    {
        $dangling = $claims->danglingClaims();

        if ($dangling !== []) {
            $this->line('  <options=bold;fg=red>Stale claims</>  <fg=gray>a page cites evidence that no longer exists</>');
            $this->newLine();
            foreach ($dangling as $page => $files) {
                foreach ($files as $file) {
                    $this->line("  <fg=red>{$page}</> <fg=gray>cites</> {$file}");
                }
            }
            $this->newLine();
        }

        $backlog = $this->inScopeList($features->backlog());

        if ($backlog !== []) {
            $this->line(sprintf('  <options=bold>Undocumented feature areas</>  <fg=gray>%d with tested behaviour — start at the top</>',
                count($backlog)));
            $this->newLine();
            foreach ($backlog as $area) {
                $this->line(sprintf('  <fg=yellow>%-24s</> <fg=gray>%d routes tested · %s%s</>',
                    $area->slug,
                    count($area->testedRoutes),
                    $area->hasHelp ? 'has inline help' : 'no help yet',
                    $area->isAdmin ? ' · admin' : '',
                ));
            }
            $this->newLine();
        }

        $documented = $this->inScopeList($features->documented());

        if ($documented !== []) {
            $this->line('  <options=bold>Documented</>  <fg=gray>area → pages</>');
            $this->newLine();
            foreach ($documented as $area) {
                $this->line(sprintf('  <fg=green>%-24s</> <fg=gray>%s</>', $area->slug, implode(', ', $area->docPages)));
            }
            $this->newLine();
        }

        if ($claims->unclaimedPages !== []) {
            // Prose with no evidence and no declared area. Not an error — most
            // are handbook pages about VU SA procedure, which no test can prove.
            $this->line(sprintf('  <options=bold>No evidence cited</>  <fg=gray>%d pages carry no tests and declare no area</>',
                count($claims->unclaimedPages)));
            $this->newLine();
            foreach ($claims->unclaimedPages as $page) {
                $this->line("      <fg=gray>{$page}</>");
            }
            $this->newLine();
        }
    }

    /**
     * @param  list<DocFreshness>  $freshness
     */
    private function renderFreshness(array $freshness): void
    {
        $drifted = array_values(array_filter($freshness, fn (DocFreshness $f) => $f->hasDrifted()));
        $neverReviewed = array_values(array_filter($freshness, fn (DocFreshness $f) => $f->neverReviewed()));

        if ($drifted !== []) {
            $this->line('  <options=bold;fg=yellow>Pages that may have drifted</>  <fg=gray>cited tests changed after the last review</>');
            $this->newLine();
            foreach ($drifted as $f) {
                $this->line(sprintf('  <options=bold>%s</> <fg=gray>reviewed %s, tests changed through %s</>',
                    $f->page, $f->reviewedAt, $f->lastChangeAt));
                foreach ($f->changedSince as $file) {
                    $this->line("      <fg=gray>{$file}</>");
                }
            }
            $this->newLine();
        }

        if ($neverReviewed !== []) {
            $this->line(sprintf('  <options=bold>Never reviewed</>  <fg=gray>%d pages cite tests but set no last_reviewed date</>',
                count($neverReviewed)));
            $this->newLine();
            foreach ($neverReviewed as $f) {
                $this->line("      <fg=gray>{$f->page}</>");
            }
            $this->newLine();
        }
    }

    /**
     * @param  list<string>  $docWarnings
     * @param  list<string>  $freshnessWarnings
     */
    private function renderWarnings(array $docWarnings, array $freshnessWarnings): void
    {
        foreach ([...$docWarnings, ...$freshnessWarnings] as $warning) {
            $this->warn("  {$warning}");
        }
    }

    // ---- review mode --------------------------------------------------------

    /**
     * Review mode: which documentation is downstream of this branch's test
     * changes, and what exactly changed underneath it.
     */
    private function reviewChanges(string $baseRef, DocClaims $claims): int
    {
        $analyzer = app(ChangedTestAnalyzer::class);
        $changed = $analyzer->analyze($baseRef);

        $this->newLine();

        foreach ($analyzer->warnings() as $warning) {
            $this->warn("  {$warning}");
        }

        if ($changed === []) {
            $this->line('  <fg=gray>No test files changed since '.$baseRef.'.</>');
            $this->newLine();

            return self::SUCCESS;
        }

        $affected = [];

        foreach ($claims->claims as $page => $citedFiles) {
            foreach ($citedFiles as $file) {
                if (isset($changed[$file])) {
                    $affected[$page][] = $changed[$file];
                }
            }
        }

        $this->renderAffectedPages($affected);
        $this->renderUncitedChanges($changed, $claims->claimedTestFiles());

        if ($this->option('summary')) {
            $this->writeReviewSummary($affected, $changed, $claims->claimedTestFiles());
        }

        return self::SUCCESS;
    }

    /**
     * @param  array<string, list<ChangedTestFile>>  $affected
     */
    private function renderAffectedPages(array $affected): void
    {
        if ($affected === []) {
            $this->line('  <fg=gray>No documentation cites the test files this branch changed.</>');
            $this->newLine();

            return;
        }

        $this->line('  <options=bold;fg=yellow>Documentation to re-check</>  <fg=gray>these pages cite tests you changed</>');
        $this->newLine();

        foreach ($affected as $page => $files) {
            $this->line("  <options=bold>{$page}</>");

            foreach ($files as $file) {
                $this->line("    <fg=gray>{$file->path}</>");

                foreach ($file->added as $name) {
                    $this->line("      <fg=green>+ {$name}</>");
                }

                foreach ($file->removed as $name) {
                    $this->line("      <fg=red>- {$name}</>");
                }
            }

            $this->newLine();
        }
    }

    /**
     * @param  array<string, ChangedTestFile>  $changed
     * @param  list<string>  $citedFiles
     */
    private function renderUncitedChanges(array $changed, array $citedFiles): void
    {
        $cited = array_flip($citedFiles);
        $uncited = array_diff_key($changed, $cited);

        if ($uncited === []) {
            return;
        }

        $added = array_sum(array_map(fn ($f) => count($f->added), $uncited));

        $this->line(sprintf('  <fg=gray>%d other test file(s) changed (%d new tests) that no page cites.</>',
            count($uncited), $added));
        $this->newLine();
    }

    // ---- summaries and dashboard --------------------------------------------

    /**
     * @param  list<DocFreshness>  $freshness
     */
    private function writeStepSummary(FeatureSurface $features, array $freshness): void
    {
        $path = getenv('GITHUB_STEP_SUMMARY');

        if ($path === false || $path === '') {
            $this->warn('  $GITHUB_STEP_SUMMARY is not set — skipping summary.');

            return;
        }

        file_put_contents($path, $this->reportMarkdown($features, $freshness), FILE_APPEND);
        $this->line('  <fg=gray>Wrote report to $GITHUB_STEP_SUMMARY.</>');
    }

    private function writeDashboard(FeatureSurface $features, array $freshness): void
    {
        $option = $this->option('dashboard-path');
        $relative = is_string($option) && $option !== '' ? $option : 'docs/maintainers/coverage.md';
        $path = base_path($relative);

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        file_put_contents($path, $this->dashboardMarkdown($features, $freshness));
        $this->line("  <fg=gray>Wrote dashboard to {$relative}.</>");
    }

    /**
     * @param  list<DocFreshness>  $freshness
     */
    private function reportMarkdown(FeatureSurface $features, array $freshness): string
    {
        $areas = array_values($features->areas);
        $areaCount = count($areas);
        $documented = $features->documentedCount();

        $md = "## Documentation coverage\n\n";
        $md .= "| | count | share |\n|---|---|---|\n";
        $md .= "| feature areas documented | `{$documented}/{$areaCount}` | ".$this->pct($documented, $areaCount)."% |\n";
        $md .= '| areas with inline help | `'.$features->withHelpCount()."/{$areaCount}` | ".$this->pct($features->withHelpCount(), $areaCount)."% |\n\n";

        $backlog = $features->backlog();

        if ($backlog !== []) {
            $md .= "### Undocumented feature areas\n\n| area | routes tested | inline help |\n|---|---|---|\n";
            foreach (array_slice($backlog, 0, 15) as $area) {
                $md .= "| `{$area->slug}` | ".count($area->testedRoutes).' | '.($area->hasHelp ? 'yes' : '—')." |\n";
            }
            $md .= "\n";
        }

        $drifted = array_values(array_filter($freshness, fn (DocFreshness $f) => $f->hasDrifted()));

        if ($drifted !== []) {
            $md .= "### Pages that may have drifted\n\n";
            foreach ($drifted as $f) {
                $md .= "- `{$f->page}` — reviewed {$f->reviewedAt}, cited tests changed through {$f->lastChangeAt}\n";
            }
            $md .= "\n";
        }

        return $md;
    }

    /**
     * @param  list<DocFreshness>  $freshness
     */
    private function dashboardMarkdown(FeatureSurface $features, array $freshness): string
    {
        $drift = [];
        foreach ($freshness as $f) {
            if ($f->hasDrifted()) {
                $drift[$f->page] = $f;
            }
        }

        $md = "# Documentation coverage\n\n";
        $md .= '> Generated by `php artisan docs:coverage --dashboard`. Do not edit by hand — ';
        $md .= "the prose lives in the pages this tracks, not here.\n\n";

        $areas = array_values($features->areas);
        $documented = $features->documentedCount();
        $md .= sprintf("**%d/%d feature areas documented · %d areas with inline help.**\n\n",
            $documented, count($areas), $features->withHelpCount());

        $backlog = $features->backlog();

        if ($backlog !== []) {
            $md .= "## Start here\n\nRanked by how much tested behaviour goes unexplained.\n\n";
            $md .= "| # | area | model | routes tested | inline help |\n|---|---|---|---|---|\n";
            foreach (array_slice($backlog, 0, 10) as $i => $area) {
                $md .= sprintf("| %d | `%s` | %s | %d | %s |\n",
                    $i + 1,
                    $area->slug,
                    $area->modelClass !== null ? class_basename($area->modelClass) : '—',
                    count($area->testedRoutes),
                    $area->hasHelp ? 'yes' : '—',
                );
            }
            $md .= "\n";
        }

        $md .= "## All feature areas\n\n";
        $md .= "| area | model | inline help | documented | routes tested | reviewed |\n|---|---|---|---|---|---|\n";
        foreach ($areas as $area) {
            $pages = $area->docPages;
            $reviewed = $this->reviewedFor($pages, $freshness);
            $md .= sprintf("| `%s` | %s | %s | %s | %d/%d | %s |\n",
                $area->slug,
                $area->modelClass !== null ? class_basename($area->modelClass) : '—',
                $area->hasHelp ? 'yes' : '—',
                $this->documentedCell($area, $drift),
                count($area->testedRoutes),
                count($area->routes),
                $reviewed,
            );
        }

        return $md."\n";
    }

    /**
     * @param  array<string, DocFreshness>  $drift
     */
    private function documentedCell(FeatureArea $area, array $drift): string
    {
        if (! $area->isDocumented()) {
            return '—';
        }

        foreach ($area->docPages as $page) {
            if (isset($drift[$page])) {
                return '⚠ drifted';
            }
        }

        return 'yes';
    }

    /**
     * @param  list<string>  $pages
     * @param  list<DocFreshness>  $freshness
     */
    private function reviewedFor(array $pages, array $freshness): string
    {
        $dates = [];

        foreach ($freshness as $f) {
            if (in_array($f->page, $pages, true) && $f->reviewedAt !== null) {
                $dates[] = $f->reviewedAt;
            }
        }

        return $dates === [] ? '—' : min($dates);
    }

    /**
     * @param  array<string, list<ChangedTestFile>>  $affected
     * @param  array<string, ChangedTestFile>  $changed
     * @param  list<string>  $citedFiles
     */
    private function writeReviewSummary(array $affected, array $changed, array $citedFiles): void
    {
        $path = getenv('GITHUB_STEP_SUMMARY');

        if ($path === false || $path === '') {
            return;
        }

        if ($affected === []) {
            file_put_contents($path, "## Documentation\n\nNo page cites the test files this branch changed.\n\n", FILE_APPEND);

            return;
        }

        $md = "## Documentation to re-check\n\nThese pages cite tests this branch changed:\n\n";

        foreach ($affected as $page => $files) {
            $md .= "### `{$page}`\n\n";

            foreach ($files as $file) {
                $md .= "`{$file->path}`\n\n";

                foreach ($file->added as $name) {
                    $md .= "- ➕ {$name}\n";
                }

                foreach ($file->removed as $name) {
                    $md .= "- ➖ {$name}\n";
                }

                $md .= "\n";
            }
        }

        file_put_contents($path, $md, FILE_APPEND);
    }

    // ---- helpers ------------------------------------------------------------

    /**
     * @return list<FeatureArea>
     */
    private function inScope(FeatureSurface $features): array
    {
        return $this->inScopeList(array_values($features->areas));
    }

    /**
     * @param  list<FeatureArea>  $areas
     * @return list<FeatureArea>
     */
    private function inScopeList(array $areas): array
    {
        if (! ($prefix = $this->option('area'))) {
            return $areas;
        }

        return array_values(array_filter($areas, fn (FeatureArea $a) => str_starts_with($a->slug, $prefix)));
    }

    private function pct(int $part, int $whole): int
    {
        return $whole > 0 ? (int) round($part / $whole * 100) : 100;
    }

    private function bar(int $part, int $whole): string
    {
        $filled = (int) round($this->pct($part, $whole) / 5);

        return sprintf('<fg=green>%s</><fg=gray>%s</> %d/%d',
            str_repeat('#', $filled), str_repeat('.', 20 - $filled), $part, $whole);
    }
}
