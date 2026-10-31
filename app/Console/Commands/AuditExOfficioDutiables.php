<?php

namespace App\Console\Commands;

use App\Actions\Dutiables\PlanExOfficioReview;
use App\Models\Duty;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/** Read-only proposals based on historical source-term overlaps. */
#[Description('Preview historical ex-officio repairs without changing rows')]
#[Signature('duties:audit-ex-officio
                            {--tenant= : Limit the report to a tenant id}
                            {--current : Show only rows active today}')]
class AuditExOfficioDutiables extends Command
{
    public function handle(): int
    {
        $duties = Duty::query()->whereHas('exOfficioSourceDuties')
            ->when($this->option('tenant') !== null, fn ($query) => $query->whereHas('institution', fn ($institution) => $institution->where('tenant_id', (int) $this->option('tenant'))))
            ->orderBy('id')->pluck('id');
        $items = collect();
        $blocked = collect();
        $bar = $this->output->createProgressBar($duties->count());
        foreach ($duties as $id) {
            $plan = PlanExOfficioReview::execute([$id]);
            $items = $items->merge($plan['items']);
            $blocked = $blocked->merge($plan['blocked']);
            $bar->advance();
        }
        $bar->finish();
        $this->newLine();
        if ($this->option('current')) {
            $items = $items->filter(function (array $item): bool {
                $period = $item['before'] ?? $item['after'];

                return $period['start_date'] <= today()->toDateString() && ($period['end_date'] === null || $period['end_date'] >= today()->toDateString());
            });
        }
        if ($items->isEmpty() && $blocked->isEmpty()) {
            $this->info('No ex-officio assignments need review.');

            return self::SUCCESS;
        }
        $this->table(['Kind', 'User', 'Duty', 'Before', 'After', 'Tenant', 'Review'], $items->sortByDesc('orphan')->map(fn (array $item) => [
            $item['kind'], $item['holder_name'], $item['duty_name'],
            $item['before'] === null ? '—' : implode(' → ', array_map(fn ($date) => $date ?? '∞', $item['before'])),
            implode(' → ', array_map(fn ($date) => $date ?? '∞', $item['after'])),
            ($item['tenant_before'] ?? '—').' → '.($item['tenant_after'] ?? '—'),
            $item['orphan'] ? 'Ends current access; choose remainder' : ($item['selected'] ? 'Preselected' : 'Choose explicitly'),
        ])->all());
        $this->table(['Kind', 'Count'], $items->countBy('kind')->map(fn (int $count, string $kind) => [$kind, $count])->values()->all());
        if ($blocked->isNotEmpty()) {
            $this->table(['User', 'Duty', 'Blocked reason'], $blocked->map(fn (array $item) => [$item['holder_name'], $item['target_duty_id'], $item['reason']])->all());
        }
        $this->info('Read only: '.$items->count().' proposals; '.$blocked->count().' blocked.');

        return self::SUCCESS;
    }
}
