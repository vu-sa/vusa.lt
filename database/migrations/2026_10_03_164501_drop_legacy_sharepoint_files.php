<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Permission;
use App\Models\User;
use App\Services\Permissions\PermissionMapBuilder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

/**
 * `sharepoint_files` + `sharepoint_fileables` were superseded by `fileable_files`, and the drive
 * browser that still read them (and gated on `sharepointFiles.*`) is gone. Links the earlier
 * backfill missed become `fileable_files` rows; SyncFileableFilesJob fills in their metadata or
 * marks them deleted externally.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sharepoint_fileables') && Schema::hasTable('sharepoint_files')) {
            $this->backfillMissingFileableFiles();

            Schema::drop('sharepoint_fileables');
            Schema::drop('sharepoint_files');
        }

        DB::table('comments')->where('commentable_type', 'sharepoint_file')->delete();

        $this->dropSharepointFilePermissions();
    }

    public function down(): void
    {
        Schema::create('sharepoint_files', function (Blueprint $table) {
            $table->string('sharepoint_id');
            $table->char('id', 36)->primary();
        });

        Schema::create('sharepoint_fileables', function (Blueprint $table) {
            $table->char('sharepoint_file_id', 36);
            $table->string('fileable_type');
            $table->char('fileable_id', 26);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();

            $table->index(['fileable_type', 'fileable_id']);
            $table->unique(['sharepoint_file_id', 'fileable_id', 'fileable_type'], 'sharepoint_fileables_unique');
            $table->foreign('sharepoint_file_id')->references('id')->on('sharepoint_files')->cascadeOnDelete();
        });
    }

    private function backfillMissingFileableFiles(): void
    {
        $missing = DB::table('sharepoint_fileables')
            ->join('sharepoint_files', 'sharepoint_files.id', '=', 'sharepoint_fileables.sharepoint_file_id')
            ->whereNotExists(fn ($query) => $query->select(DB::raw(1))
                ->from('fileable_files')
                ->whereColumn('fileable_files.sharepoint_id', 'sharepoint_files.sharepoint_id')
                ->whereColumn('fileable_files.fileable_id', 'sharepoint_fileables.fileable_id'))
            ->get(['sharepoint_files.sharepoint_id', 'sharepoint_fileables.fileable_type', 'sharepoint_fileables.fileable_id', 'sharepoint_fileables.created_at']);

        foreach ($missing as $link) {
            DB::table('fileable_files')->insert([
                'id' => (string) Str::ulid(),
                'fileable_type' => $link->fileable_type,
                'fileable_id' => $link->fileable_id,
                'sharepoint_id' => $link->sharepoint_id,
                'name' => 'SharePoint failas',
                'created_at' => $link->created_at,
                'updated_at' => now(),
            ]);
        }
    }

    private function dropSharepointFilePermissions(): void
    {
        $permissions = Permission::query()->where('name', 'like', 'sharepointFiles.%')->with('roles')->get();

        if ($permissions->isEmpty()) {
            return;
        }

        $affectedUsers = $permissions->flatMap(fn (Permission $permission) => $permission->roles)
            ->unique('id')
            ->flatMap(fn ($role) => $role->usersThroughDuties->merge($role->users))
            ->merge(User::permission($permissions->pluck('name')->all())->get())
            ->unique('id');

        $permissions->each->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $affectedUsers->each(function (User $user): void {
            PermissionMapBuilder::forgetCachedMaps($user->id);
            Cache::forget(HandleInertiaRequests::adminNavigationCacheKey($user->id));
        });
    }
};
