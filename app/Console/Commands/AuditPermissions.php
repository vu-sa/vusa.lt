<?php

namespace App\Console\Commands;

use App\Models\Permission;
use App\Models\Role;
use App\Support\Permissions\PermissionCatalog;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Reports only: a permission a role still holds is real access, so removing one is a human's call.
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

        $this->table(['Metric', 'Count'], [
            ['Missing', count($report['missing'])],
            ['Orphans', count($report['orphans'])],
            ['Orphans still held', count(array_filter($report['orphans'], fn (array $orphan): bool => $orphan['held_by'] !== []))],
            ['Empty responsibilities', count($report['empty_responsibilities'])],
        ]);

        return self::SUCCESS;
    }

    /**
     * @return array{
     *     missing: list<string>,
     *     orphans: list<array{name: string, held_by: list<string>}>,
     *     empty_responsibilities: list<string>,
     * }
     */
    private function report(): array
    {
        $stored = Permission::query()->pluck('name');

        return [
            'missing' => PermissionCatalog::missingIn($stored),
            'orphans' => array_map(
                fn (string $name): array => ['name' => $name, 'held_by' => $this->holdersOf($name)],
                PermissionCatalog::orphansIn($stored),
            ),
            'empty_responsibilities' => Role::query()
                ->where('name', '!=', config('permission.super_admin_role_name'))
                ->doesntHave('permissions')
                ->get()
                ->map(fn (Role $role): string => $role->name)
                ->values()
                ->all(),
        ];
    }

    /**
     * @return list<string>
     */
    private function holdersOf(string $permission): array
    {
        $viaRoles = DB::table('role_has_permissions')
            ->join('permissions', 'permissions.id', '=', 'role_has_permissions.permission_id')
            ->join('roles', 'roles.id', '=', 'role_has_permissions.role_id')
            ->where('permissions.name', $permission)
            ->pluck('roles.name');

        $direct = DB::table('model_has_permissions')
            ->join('permissions', 'permissions.id', '=', 'model_has_permissions.permission_id')
            ->where('permissions.name', $permission)
            ->get(['model_has_permissions.model_type', 'model_has_permissions.model_id'])
            ->map(fn (object $row): string => "{$row->model_type}:{$row->model_id}");

        return $viaRoles->merge($direct)->values()->all();
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
