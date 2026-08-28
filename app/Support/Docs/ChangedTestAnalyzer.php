<?php

namespace App\Support\Docs;

use Symfony\Component\Process\Process;

/**
 * Works out which tests a branch added or removed, so a reviewer can be told
 * which documentation pages might now be describing the wrong thing.
 *
 * This closes the gap the route report cannot see: adding a test to a file that
 * a doc page already cites changes nothing about route coverage, but it very
 * often means the page is now incomplete.
 */
class ChangedTestAnalyzer
{
    /** @var list<string> */
    private array $warnings = [];

    public function __construct(private readonly TestSurfaceScanner $scanner) {}

    /**
     * @return array<string, ChangedTestFile> keyed by repo-relative test path
     */
    public function analyze(string $baseRef): array
    {
        $changed = [];

        foreach ($this->changedTestFiles($baseRef) as $path) {
            $before = $this->scanner->testNamesIn($this->fileAtRef($baseRef, $path) ?? '<?php');
            $after = is_file(base_path($path))
                ? $this->scanner->testNamesIn((string) file_get_contents(base_path($path)))
                : [];

            $added = array_values(array_diff($after, $before));
            $removed = array_values(array_diff($before, $after));

            if ($added === [] && $removed === []) {
                continue;
            }

            $changed[$path] = new ChangedTestFile($path, $added, $removed);
        }

        ksort($changed);

        return $changed;
    }

    /**
     * Diagnostics for a run that could not diff cleanly — a shallow clone or a
     * missing base ref must announce itself, not read as "nothing changed".
     *
     * @return list<string>
     */
    public function warnings(): array
    {
        return $this->warnings;
    }

    /**
     * @return list<string>
     */
    private function changedTestFiles(string $baseRef): array
    {
        // Diff the working tree against the point this branch diverged, so the
        // same command answers for uncommitted local work and for a PR branch,
        // without dragging in whatever has landed on the base branch since.
        $mergeBase = $this->git(['merge-base', $baseRef, 'HEAD'], warnOnFailure: true);
        $mergeBase = $mergeBase !== null ? (trim($mergeBase) ?: $baseRef) : $baseRef;

        $output = $this->git(['diff', '--name-only', $mergeBase], warnOnFailure: true);

        if ($output === null) {
            return [];
        }

        return array_values(array_filter(
            array_map(trim(...), explode("\n", $output)),
            fn (string $p) => $p !== '' && str_starts_with($p, 'tests/') && str_ends_with($p, '.php'),
        ));
    }

    private function fileAtRef(string $ref, string $path): ?string
    {
        // Absent at the base ref means the whole file is new; every test in it
        // then reads as added, which is what a reviewer wants to see — so a
        // failure here is expected and must not warn.
        return $this->git(['show', $ref.':'.$path], warnOnFailure: false);
    }

    /**
     * @param  list<string>  $arguments
     */
    private function git(array $arguments, bool $warnOnFailure): ?string
    {
        $process = new Process(['git', ...$arguments], base_path());
        $process->run();

        if ($process->isSuccessful()) {
            return $process->getOutput();
        }

        if ($warnOnFailure) {
            $this->warnings[] = 'git '.implode(' ', $arguments).' failed: '.trim($process->getErrorOutput());
        }

        return null;
    }
}
