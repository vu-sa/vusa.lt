<?php

namespace App\Console\Commands;

use App\Actions\Dutiables\FindSuspectExOfficioDutiables;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

/**
 * Report duties a user still holds ex officio although they no longer hold the
 * duty that granted them.
 *
 * Until DutiableChanged was fixed, deleting a source dutiable never cascaded to the
 * rows derived from it: the queued listener could not restore the deleted model, so
 * the cleanup never ran. The `via_dutiable_id` foreign key is `nullOnDelete()`, so
 * those derived rows survived with a NULL link — indistinguishable from a duty that
 * was assigned by hand.
 *
 * That ambiguity is why this command only reports. The rows it lists are *suspects*;
 * some will be legitimate manual assignments, and they grant real permissions, so
 * deciding which to remove is a human's job.
 */
#[Description('Report active ex-officio duties whose granting source duty is no longer held')]
#[Signature('duties:audit-ex-officio
                            {--tenant= : Limit the report to a tenant id}')]
class AuditExOfficioDutiables extends Command
{
    public function handle(): int
    {
        $this->info('🔍 Auditing ex-officio dutiables');
        $this->newLine();

        $rows = $this->findSuspectRows();

        if ($rows->isEmpty()) {
            $this->info('✅ No orphaned ex-officio duties found.');

            return self::SUCCESS;
        }

        $this->table(
            ['User', 'Email', 'Target duty', 'Missing source duty', 'Tenant', 'Since'],
            $rows->map(fn ($row) => [
                $row->user_name,
                $row->user_email,
                $this->localized($row->target_duty),
                $this->localized($row->source_duty),
                $row->tenant ?? '—',
                $row->start_date,
            ])->all()
        );

        $this->newLine();
        $this->table(
            ['Metric', 'Count'],
            [
                // A target duty can have several source duties, so one duty row can
                // appear once per source it could have come from.
                ['Suspect duty rows', $rows->pluck('dutiable_id')->unique()->count()],
                ['(row, possible source) pairs', $rows->count()],
                ['Users affected', $rows->pluck('user_id')->unique()->count()],
            ]
        );

        $this->newLine();
        $this->warn('These rows are suspects, not proven orphans — a NULL via_dutiable_id looks');
        $this->warn('exactly like a manual assignment. Review before removing anything.');

        return self::SUCCESS;
    }

    /**
     * Duty names are translatable, so the raw column holds a JSON object rather
     * than a string. The query bypasses Eloquent, so unwrap it here.
     */
    private function localized(?string $value): string
    {
        if ($value === null) {
            return '—';
        }

        $decoded = json_decode($value, true);

        if (! is_array($decoded)) {
            return $value;
        }

        return $decoded[app()->getLocale()] ?? $decoded['lt'] ?? reset($decoded) ?: '—';
    }

    /**
     * Active rows on an ex-officio target duty whose holder no longer holds the
     * matching source duty.
     *
     * @return Collection<int, \stdClass>
     */
    private function findSuspectRows(): Collection
    {
        $tenantId = $this->option('tenant');

        // Shared with the dutiable timeline's diagnostics, so both report the same rows.
        $query = FindSuspectExOfficioDutiables::query($tenantId === null ? null : (int) $tenantId)
            ->select([
                't.id as dutiable_id',
                't.dutiable_id as user_id',
                'u.name as user_name',
                'u.email as user_email',
                'target.name as target_duty',
                'source.name as source_duty',
                'tn.shortname as tenant',
                't.start_date',
            ])
            ->orderBy('u.name');

        return collect($query->get());
    }
}
