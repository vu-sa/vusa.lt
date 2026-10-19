<?php

namespace App\Console\Commands;

use App\Actions\GenerateInstitutionAlias;
use App\Actions\NormalizeInstitutionAliases as NormalizeInstitutionAliasesAction;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Description('Regenerate institution aliases from their names; old addresses keep redirecting.')]
#[Signature('institutions:normalize-aliases
                            {--dry-run : Report proposed changes and the stored links they affect, without writing}
                            {--force : Skip the confirmation prompt}')]
class NormalizeInstitutionAliases extends Command
{
    public function handle(NormalizeInstitutionAliasesAction $normalizer): int
    {
        $plan = $normalizer->plan();

        if ($plan === []) {
            $this->info('Nothing to change — every alias already matches its name.');

            return self::SUCCESS;
        }

        $this->table(
            ['Tenant', 'Old alias', 'New alias', 'Old address'],
            collect($plan)->sortBy('tenant')->map(fn (array $change): array => [
                $change['tenant'] ?? '—', $change['old'], $change['new'], $change['redirect'] ? 'redirects' : '404',
            ])->values()->all(),
        );

        $this->reportStoredLinks(array_column($plan, 'redirect', 'old'));

        if ($this->option('dry-run')) {
            $this->info(sprintf('%d alias(es) would change.', count($plan)));

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm(sprintf('Apply %d alias change(s)?', count($plan)))) {
            $this->info('Skipped.');

            return self::SUCCESS;
        }

        $progress = $this->output->createProgressBar(count($plan));
        $changed = $normalizer->apply($plan, fn () => $progress->advance());
        $progress->finish();
        $this->newLine();

        $this->info(sprintf('Changed %d alias(es).', $changed));

        return self::SUCCESS;
    }

    /**
     * Listed so an editor can update them by hand, most urgently the ones that will 404.
     *
     * @param  array<string, bool>  $oldAliases  old alias => whether it keeps redirecting
     */
    private function reportStoredLinks(array $oldAliases): void
    {
        // A reserved slug in a stored link is a contacts route, not an institution.
        $oldAliases = array_diff_key($oldAliases, array_flip(GenerateInstitutionAlias::reserved()));
        $rows = [];

        foreach (['navigation' => 'url', 'quick_links' => 'link', 'content_parts' => 'json_content'] as $table => $column) {
            DB::table($table)
                ->where(fn ($query) => $query->where($column, 'like', '%kontaktai%')->orWhere($column, 'like', '%contacts%'))
                ->get(['id', $column])
                ->each(function (object $row) use ($table, $column, $oldAliases, &$rows): void {
                    preg_match_all('#(?:kontaktai|contacts)\\\\?/([^"\'?\#<>\s\\\\]+)#u', rawurldecode((string) $row->{$column}), $matches);

                    foreach (array_unique($matches[1]) as $alias) {
                        if (isset($oldAliases[$alias])) {
                            $rows[] = [$table, $row->id, $alias, $oldAliases[$alias] ? 'redirects' : '404'];
                        }
                    }
                });
        }

        if ($rows === []) {
            return;
        }

        $this->warn('Stored links pointing at a renamed alias:');
        $this->table(['Table', 'Id', 'Old alias', 'Old address'], $rows);
    }
}
