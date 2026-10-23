<?php

use App\Actions\ForgetUserAccess;
use App\Models\User;
use App\Support\MorphMap;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\PermissionRegistrar;

/**
 * Data-only; delete with its test once it has run on production.
 *
 * A role held by a user directly applies in every padalinys they hold any duty in, so only
 * Super Admin may stay on an account; everything else reaches people through duties.
 */
return new class extends Migration
{
    public function up(): void
    {
        $superAdminRoleId = DB::table('roles')->where('name', config('permission.super_admin_role_name'))->value('id');

        $direct = DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->whereIn('model_has_roles.model_type', [MorphMap::alias(User::class), User::class])
            ->when($superAdminRoleId !== null, fn ($query) => $query->where('model_has_roles.role_id', '!=', $superAdminRoleId))
            ->get(['model_has_roles.model_id', 'model_has_roles.model_type', 'model_has_roles.role_id', 'roles.name']);

        if ($direct->isEmpty()) {
            return;
        }

        // The pre-deploy backup restores rows; this line says which ones went.
        Log::info('Removed roles held directly by users', $direct->map(fn ($row): array => ['user_id' => $row->model_id, 'role' => $row->name])->all());

        foreach ($direct as $row) {
            DB::table('model_has_roles')
                ->where('model_id', $row->model_id)
                ->where('model_type', $row->model_type)
                ->where('role_id', $row->role_id)
                ->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        ForgetUserAccess::execute($direct->pluck('model_id')->unique()->all());
    }

    public function down(): void
    {
        // Removed assignments come back only from the pre-deployment database backup.
    }
};
