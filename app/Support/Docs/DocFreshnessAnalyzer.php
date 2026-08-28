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

        foreach ($claims->claims as $page => $testFiles) {
            $reviewedAt = $claims->reviewedAt($page);
            $lastChange = null;
            $changedSince = [];

            foreach ($testFiles as $file) {
                $committedAt = $this->lastCommitDate($file);

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
     * Date of the last commit that touched a file, as `YYYY-MM-DD`. Comparing
     * dates rather than timestamps keeps it aligned with `last_reviewed`, which
     * a person writes as a plain date.
     */
    private function lastCommitDate(string $path): ?string
    {
        $process = new Process(['git', 'log', '-1', '--format=%cs', '--', $path], $this->workingDir ?? base_path());
        $process->run();

        if (! $process->isSuccessful()) {
            $this->warnings[] = "git log failed for {$path}: ".trim($process->getErrorOutput());

            return null;
        }

        $date = trim($process->getOutput());

        return $date === '' ? null : $date;
    }
}
