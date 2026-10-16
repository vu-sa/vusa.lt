<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Models\Role;
use App\Models\User;
use App\Services\ModelAuthorizer;
use App\Services\Permissions\PermissionMapBuilder;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const array ROLE_SCOPES = [
        'Studentų atstovų koordinatorius' => 'padalinys',
        'Centrinio biuro studentų atstovų koordinatorius' => '*',
    ];

    public function up(): void
    {
        Schema::table('mailing_lists', function (Blueprint $table): void {
            $table->string('display_name', 255)->change();
            $table->string('archive_head_hash')->nullable();
            $table->unsignedInteger('archive_backfill_offset')->default(0);
            $table->unsignedInteger('archive_backfill_total')->default(0);
            $table->timestamp('archive_backfilled_at')->nullable();
        });

        foreach (['mailing_list_sources', 'mailing_list_exclusions', 'mailing_list_members'] as $name) {
            Schema::table($name, function (Blueprint $table) use ($name): void {
                $table->string('email', 255)->nullable($name === 'mailing_list_sources')->change();
                if ($name !== 'mailing_list_exclusions') {
                    $table->string('display_name', 255)->nullable()->change();
                }
                if ($name !== 'mailing_list_members') {
                    $table->string('note', 255)->nullable()->change();
                }
            });
        }
        Schema::table('mailing_list_messages', function (Blueprint $table): void {
            foreach (['subject', 'sender_name', 'sender_email'] as $column) {
                $table->string($column, 255)->nullable()->change();
            }
        });

        foreach (self::ROLE_SCOPES as $name => $scope) {
            $role = Role::query()->where('name', $name)->first();
            if ($role === null) {
                continue;
            }
            $role->givePermissionTo(array_map(fn (string $action) => "mailingLists.{$action}.{$scope}", ['read', 'create', 'update']));
            $this->forgetAccessCaches($role);
        }
    }

    public function down(): void
    {
        foreach (self::ROLE_SCOPES as $name => $scope) {
            $role = Role::query()->where('name', $name)->first();
            if ($role === null) {
                continue;
            }
            $role->revokePermissionTo(array_map(fn (string $action) => "mailingLists.{$action}.{$scope}", ['read', 'create', 'update']));
            $this->forgetAccessCaches($role);
        }

        Schema::table('mailing_lists', fn (Blueprint $table) => $table->dropColumn([
            'archive_head_hash', 'archive_backfill_offset', 'archive_backfill_total', 'archive_backfilled_at',
        ]));
    }

    private function forgetAccessCaches(Role $role): void
    {
        $role->usersThroughDuties->merge($role->users)->each(function (User $user): void {
            app(ModelAuthorizer::class)->resetCache($user);
            PermissionMapBuilder::forgetCachedMaps($user->id);
            Cache::forget(HandleInertiaRequests::adminNavigationCacheKey($user->id));
        });
    }
};
