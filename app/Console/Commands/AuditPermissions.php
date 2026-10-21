<?php

namespace App\Console\Commands;

use App\Actions\Permissions\SyncPermissionCatalog;
use App\Models\DutyType;
use App\Models\Permission;
use App\Models\Role;
use App\Support\MorphMap;
use App\Support\Permissions\PermissionCatalog;
use App\Support\Permissions\TypeGrantablePermissions;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Reports only; `permissions:sync` applies the changes. Unsafe type grants are fixed by hand.
 */
#[Description('Compare stored permissions and responsibilities with the permission catalog')]
#[Signature('permissions:audit {--json : Print the report as JSON}')]
class AuditPermissions extends Command
{
    public function handle(): int
    {
        $report = $this->report();

        if ($this->option('json')) {
            $this->line((string) json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return self::SUCCESS;
        }

        $this->info('🔍 Auditing permissions');
        $this->newLine();

        $this->section('Missing from the database', ['Permission'], array_map(fn (string $name): array => [$name], $report['missing']));
        $this->section('Not in the catalog', ['Permission', 'Held by'], array_map(
            fn (array $orphan): array => [$orphan['name'], implode(', ', $orphan['held_by']) ?: '—'],
            $report['orphans'],
        ));
        $this->section('Responsibilities without permissions', ['Responsibility'], array_map(fn (string $title): array => [$title], $report['empty_responsibilities']));
        $this->section('Given to duty types despite wide permissions', ['Responsibility', 'Permissions'], array_map(
            fn (array $row): array => [$row['name'], implode(', ', $row['permissions'])],
            $report['unsafe_type_grants'],
        ));

        $this->table(['Metric', 'Count'], [
            ['Missing', count($report['missing'])],
            ['Orphans', count($report['orphans'])],
            ['Orphans still held', count(array_filter($report['orphans'], fn (array $orphan): bool => $orphan['held_by'] !== []))],
            ['Empty responsibilities', count($report['empty_responsibilities'])],
            ['Unsafe type grants', count($report['unsafe_type_grants'])],
        ]);

        return self::SUCCESS;
    }

    /**
     * @return array{
     *     missing: list<string>,
     *     orphans: list<array{name: string, held_by: list<string>}>,
     *     empty_responsibilities: list<string>,
     *     unsafe_type_grants: list<array{name: string, permissions: list<string>}>,
     * }
     */
    private function report(): array
    {
        $stored = Permission::query()->pluck('name');

        return [
            'missing' => PermissionCatalog::missingIn($stored),
            'orphans' => array_map(
                fn (string $name): array => ['name' => $name, 'held_by' => SyncPermissionCatalog::holdersOf($name)],
                PermissionCatalog::orphansIn($stored),
            ),
            'empty_responsibilities' => Role::query()
                ->where('name', '!=', config('permission.super_admin_role_name'))
                ->doesntHave('permissions')
                ->get()
                ->map(fn (Role $role): string => $role->name)
                ->values()
                ->all(),
            'unsafe_type_grants' => Role::query()
                ->whereHas('grants', fn ($grants) => $grants->where('grantee_type', MorphMap::alias(DutyType::class)))
                ->with('permissions:id,name')
                ->get()
                ->map(fn (Role $role): array => ['name' => $role->name, 'permissions' => TypeGrantablePermissions::violations($role->permissions->pluck('name'))])
                ->filter(fn (array $row): bool => $row['permissions'] !== [])
                ->values()
                ->all(),
        ];
    }

    /**
     * @param  list<string>  $headers
     * @param  list<list<string>>  $rows
     */
    private function section(string $title, array $headers, array $rows): void
    {
        $this->line("<comment>{$title}</comment>");

        $rows === [] ? $this->line('  ✅ none') : $this->table($headers, $rows);

        $this->newLine();
    }
}
