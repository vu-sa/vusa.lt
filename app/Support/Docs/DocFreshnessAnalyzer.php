<?php

namespace App\Support\Docs;

use Symfony\Component\Process\Process;

/**
 * The standing rot radar: for each page that cites tests, is the newest change
 * to those tests more recent than the last time a human reviewed the page?
 *
 * Unlike {@see ChangedTestAnalyzer}, which answers "what did this branch touch",
 * this answers "which pages have quietly fallen out of date" at any moment — the
 * question that keeps documentation honest between deliberate rewrites.
 */
class DocFreshnessAnalyzer
{
    /** @var list<string> */
    private array $warnings = [];

    public function __construct(private readonly ?string $workingDir = null) {}

    /**
     * @return list<DocFreshness> pages that cite tests, ordered by path
     */
    public function analyze(DocClaims $claims): array
    {
        $freshness = [];
        $commitDates = $this->lastCommitDates(array_values(array_unique(array_merge(...array_values($claims->claims)))));

        foreach ($claims->claims as $page => $testFiles) {
            $reviewedAt = $claims->reviewedAt($page);
            $lastChange = null;
            $changedSince = [];

            foreach ($testFiles as $file) {
                $committedAt = $commitDates[$file] ?? null;

                if ($committedAt === null) {
                    continue;
                }

                if ($lastChange === null || $committedAt > $lastChange) {
                    $lastChange = $committedAt;
                }

                if ($reviewedAt !== null && $committedAt > $reviewedAt) {
                    $changedSince[] = $file;
                }
            }

            $freshness[] = new DocFreshness($page, $reviewedAt, $lastChange, $changedSince);
        }

        usort($freshness, fn (DocFreshness $a, DocFreshness $b) => strcmp($a->page, $b->page));

        return $freshness;
    }

    /**
     * @return list<string>
     */
    public function warnings(): array
    {
        return $this->warnings;
    }

    /**
     * Date of the last commit that touched each file, as `YYYY-MM-DD` (dates, to match the
     * hand-written `last_reviewed`). One newest-first `git log` stopped once every path is
     * dated: a process per path cost ~1.5s per run.
     *
     * @param  list<string>  $paths
     * @return array<string, string>
     */
    private function lastCommitDates(array $paths): array
    {
        if ($paths === []) {
            return [];
        }

        $process = new Process(['git', 'log', '--format=%x00%cs', '--name-only', '--diff-merges=combined', '--', ...$paths], $this->workingDir ?? base_path());
        $process->start();

        $wanted = array_flip($paths);
        $dates = [];
        $commitDate = null;
        $buffer = '';

        foreach ($process->getIterator($process::ITER_SKIP_ERR) as $chunk) {
            $buffer .= $chunk;
            $lines = explode("\n", $buffer);
            $buffer = array_pop($lines);

            foreach ($lines as $line) {
                if (str_starts_with($line, "\0")) {
                    $commitDate = substr($line, 1);
                } elseif ($line !== '' && isset($wanted[$line]) && ! isset($dates[$line])) {
                    $dates[$line] = $commitDate;
                }
            }

            if (count($dates) === count($wanted)) {
                $process->stop();

                return $dates;
            }
        }

        if (! $process->isSuccessful()) {
            $this->warnings[] = 'git log failed: '.trim($process->getErrorOutput());
        }

        return $dates;
    }
}
