<?php

use App\Actions\ProjectDutyResponsibilities;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

/**
 * Transitional(responsibility-grants): data-only; delete with its test once it has run on production.
 *
 * Moves duty roles, type → role links and coordination assignments onto responsibility grants
 * without changing anyone's access or audience: a purpose only receives the grants that already
 * put people in that group.
 */
return new class extends Migration
{
    private const string REPRESENTATIVE_ROLE = 'Studentų atstovas';

    private const string REPRESENTATIVE_ACCESS = 'studentu-atstovo-prieiga';

    private const string COORDINATION = 'studentu-atstovu-koordinavimas';

    public function up(): void
    {
        DB::transaction(function (): void {
            $this->backfillTitles();

            $representativeRoleId = DB::table('roles')->where('name', self::REPRESENTATIVE_ROLE)->value('id');
            $registrationRoleId = $this->registrationRoleId();

            $this->grantTypeLinks();
            $this->grantDirectRoles($representativeRoleId, $registrationRoleId);
            $this->grantCoordination();

            $this->setPurpose($representativeRoleId, 'student_representation');
            $this->setPurpose($registrationRoleId, 'member_registration');
        });

        ProjectDutyResponsibilities::all();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $representativeRoleId = DB::table('roles')->where('purpose', 'student_representation')->value('id');
        $accessRoleId = DB::table('roles')->where('name', self::REPRESENTATIVE_ACCESS)->value('id');

        // Give the representative role back to the duties that held the access-only copy.
        if ($representativeRoleId !== null && $accessRoleId !== null) {
            DB::table('model_has_roles')->insertOrIgnore(
                DB::table('model_has_roles')->where('role_id', $accessRoleId)->get()
                    ->map(fn (object $row): array => ['role_id' => $representativeRoleId, 'model_type' => $row->model_type, 'model_id' => $row->model_id])
                    ->all(),
            );
        }

        DB::table('roles')->whereIn('name', [self::REPRESENTATIVE_ACCESS, self::COORDINATION])->pluck('id')
            ->each(function (string $roleId): void {
                DB::table('model_has_roles')->where('role_id', $roleId)->delete();
                DB::table('role_has_permissions')->where('role_id', $roleId)->delete();
                DB::table('roles')->where('id', $roleId)->delete();
            });

        DB::table('responsibility_grants')->delete();
        DB::table('roles')->update(['purpose' => null, 'title' => null, 'updated_at' => DB::raw('updated_at')]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function backfillTitles(): void
    {
        foreach (DB::table('roles')->whereNull('title')->get(['id', 'name']) as $role) {
            DB::table('roles')->where('id', $role->id)->update([
                'title' => json_encode(['lt' => $role->name], JSON_UNESCAPED_UNICODE),
                'updated_at' => DB::raw('updated_at'),
            ]);
        }
    }

    private function registrationRoleId(): ?string
    {
        $payload = DB::table('settings')
            ->where('group', 'forms')
            ->where('name', 'member_registration_notification_recipient_role_id')
            ->value('payload');

        $roleId = $payload === null ? null : json_decode($payload, true);

        return is_string($roleId) && DB::table('roles')->where('id', $roleId)->exists() ? $roleId : null;
    }

    private function grantTypeLinks(): void
    {
        $this->insertGrants(DB::table('duty_type_role')->get(['role_id', 'duty_type_id'])
            ->map(fn (object $link): array => $this->grant($link->role_id, 'duty_type', (string) $link->duty_type_id)));
    }

    private function grantDirectRoles(?string $representativeRoleId, ?string $registrationRoleId): void
    {
        $explained = DB::table('duty_duty_type')
            ->join('duty_type_role', 'duty_type_role.duty_type_id', '=', 'duty_duty_type.duty_type_id')
            ->get(['duty_duty_type.duty_id', 'duty_type_role.role_id'])
            ->map(fn (object $row): string => "{$row->duty_id}|{$row->role_id}")
            ->flip();

        $rows = DB::table('model_has_roles')
            ->where('model_type', 'duty')
            ->get(['role_id', 'model_id'])
            ->reject(fn (object $row): bool => $explained->has("{$row->model_id}|{$row->role_id}"));

        $tenantOf = DB::table('duties')
            ->join('institutions', 'institutions.id', '=', 'duties.institution_id')
            ->whereIn('duties.id', $rows->pluck('model_id'))
            ->pluck('institutions.tenant_id', 'duties.id');

        $accessRoleId = null;

        $this->insertGrants($rows->map(function (object $row) use ($representativeRoleId, $registrationRoleId, $tenantOf, &$accessRoleId): ?array {
            // Representative access given by hand did not make anyone a representative.
            if ($row->role_id === $representativeRoleId) {
                $accessRoleId ??= $this->cloneRole($representativeRoleId, self::REPRESENTATIVE_ACCESS, [
                    'lt' => 'Studentų atstovo prieiga',
                    'en' => 'Student representative access',
                ]);

                return $this->grant($accessRoleId, 'duty', $row->model_id);
            }

            if ($row->role_id === $registrationRoleId) {
                $tenantId = $tenantOf[$row->model_id] ?? null;

                return $tenantId === null ? null : $this->grant($row->role_id, 'duty', $row->model_id, 'tenant', (string) $tenantId);
            }

            return $this->grant($row->role_id, 'duty', $row->model_id);
        })->filter());
    }

    private function grantCoordination(): void
    {
        $assignments = DB::table('duty_responsibilities')->where('responsibility', 'student_rep_coordination')->get();

        if ($assignments->isEmpty()) {
            return;
        }

        // Permissions stay with the duties' current roles; this responsibility only carries the purpose.
        $roleId = $this->createRole(self::COORDINATION, ['lt' => 'Studentų atstovų koordinavimas', 'en' => 'Student representative coordination'], 'student_rep_coordination');

        $this->insertGrants($assignments->map(fn (object $assignment): array => $this->grant(
            $roleId, 'duty', $assignment->duty_id, $assignment->scope_type, (string) $assignment->scope_id,
        )));
    }

    /**
     * @param  array{lt: string, en: string}  $title
     */
    private function cloneRole(string $sourceId, string $name, array $title): string
    {
        $roleId = $this->createRole($name, $title, null);

        DB::table('role_has_permissions')->insert(
            DB::table('role_has_permissions')->where('role_id', $sourceId)->pluck('permission_id')
                ->map(fn ($permissionId): array => ['permission_id' => $permissionId, 'role_id' => $roleId])
                ->all(),
        );

        return $roleId;
    }

    /**
     * @param  array{lt: string, en: string}  $title
     */
    private function createRole(string $name, array $title, ?string $purpose): string
    {
        $roleId = strtolower((string) Str::ulid());

        DB::table('roles')->insert([
            'id' => $roleId,
            'name' => $name,
            'title' => json_encode($title, JSON_UNESCAPED_UNICODE),
            'purpose' => $purpose,
            'guard_name' => 'web',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $roleId;
    }

    private function setPurpose(?string $roleId, string $purpose): void
    {
        if ($roleId !== null) {
            DB::table('roles')->where('id', $roleId)->update(['purpose' => $purpose, 'updated_at' => DB::raw('updated_at')]);
        }
    }

    /**
     * @return array<string, string|null>
     */
    private function grant(string $roleId, string $granteeType, string $granteeId, ?string $scopeType = null, ?string $scopeId = null): array
    {
        return [
            'id' => strtolower((string) Str::ulid()),
            'role_id' => $roleId,
            'grantee_type' => $granteeType,
            'grantee_id' => $granteeId,
            'scope_type' => $scopeType,
            'scope_id' => $scopeId,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * @param  Collection<int, array<string, string|null>>  $grants
     */
    private function insertGrants(Collection $grants): void
    {
        foreach ($grants->chunk(500) as $chunk) {
            DB::table('responsibility_grants')->insertOrIgnore($chunk->values()->all());
        }
    }
};
