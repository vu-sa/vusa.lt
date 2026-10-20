<?php

use App\Models\Permission;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /** Resources no longer in the permission catalog. A row anyone still holds is left for a human. */
    private const array RETIRED_RESOURCES = [
        'contacts',
        'saziningaiExamFlows',
        'saziningaiExamObservers',
        'saziningaiExams',
        'sharepointFileables',
        'types',
    ];

    public function up(): void
    {
        Permission::query()
            ->where(function ($query): void {
                foreach (self::RETIRED_RESOURCES as $resource) {
                    $query->orWhere('name', 'like', "{$resource}.%");
                }
            })
            ->whereNotIn('id', DB::table('role_has_permissions')->select('permission_id'))
            ->whereNotIn('id', DB::table('model_has_permissions')->select('permission_id'))
            ->get()
            ->each->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Nobody held them, so there is nothing to give back.
    }
};
