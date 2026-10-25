<?php

use App\Actions\ProjectDutyResponsibilities;
use App\Enums\ResponsibilityPurpose;
use App\Models\Duty;
use App\Models\Role;
use App\Tasks\Synchronizers\ReservationReviewTaskSynchronizer;
use Database\Seeders\RoleReservationHandlerSeeder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

// Transitional(reservation-handling): remove this data migration and its test after production rollout.
return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            (new RoleReservationHandlerSeeder)->run();
            $role = Role::forPurpose(ResponsibilityPurpose::ReservationHandling);
            $permission = config('permission.resource_managership_indicating_permission');
            $names = [$permission, Str::beforeLast($permission, '.').'.*'];

            Duty::query()->whereHas('institution.tenant')
                ->whereHas('roles.permissions', fn (Builder $query) => $query->whereIn('name', $names))
                ->with('institution')->eachById(function (Duty $duty) use ($role): void {
                    $exists = DB::table('responsibility_grants')
                        ->where('role_id', $role->id)->where('grantee_type', $duty->getMorphClass())
                        ->where('grantee_id', $duty->id)->where('scope_type', 'tenant')
                        ->where('scope_id', (string) $duty->institution->tenant_id)->exists();

                    if (! $exists) {
                        DB::table('responsibility_grants')->insert([
                            'id' => (string) Str::ulid(), 'role_id' => $role->id,
                            'grantee_type' => $duty->getMorphClass(), 'grantee_id' => $duty->id,
                            'scope_type' => 'tenant', 'scope_id' => (string) $duty->institution->tenant_id,
                            'created_at' => now(), 'updated_at' => now(),
                        ]);
                    }
                });

            ProjectDutyResponsibilities::all();
            app(ReservationReviewTaskSynchronizer::class)->reconcileAll(false);
        });
    }

    public function down(): void
    {
        // Later edits may reuse this responsibility, so rollback only clears the new purpose.
        DB::table('roles')->where('purpose', ResponsibilityPurpose::ReservationHandling->value)
            ->update(['purpose' => null, 'updated_at' => DB::raw('updated_at')]);
    }
};
