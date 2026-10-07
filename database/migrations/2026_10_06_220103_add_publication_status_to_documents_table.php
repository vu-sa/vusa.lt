<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Every SharePoint archive file now gets a row; `status` says whether it is shown on vusa.lt.
 *
 * Ids are untouched, so the `/d/{hashid}` short links of existing documents keep resolving.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table): void {
            $table->string('status', 20)->default('pending')->after('is_active');
            $table->timestamp('removed_from_sharepoint_at')->nullable()->after('status');
            $table->timestamp('published_at')->nullable()->after('removed_from_sharepoint_at');
            $table->char('published_by', 26)->nullable()->after('published_at');
            $table->string('sharepoint_drive_item_id', 100)->nullable()->after('sharepoint_list_id');
            $table->string('sharepoint_path', 1000)->nullable()->after('sharepoint_drive_item_id');
            $table->text('sharepoint_web_url')->nullable()->after('sharepoint_path');
            $table->timestamp('sharepoint_modified_at')->nullable()->after('sharepoint_web_url');
            $table->string('sharepoint_institution_label', 255)->nullable()->after('sharepoint_modified_at');

            $table->foreign('published_by')->references('id')->on('users')->nullOnDelete();
            $table->index('status');
            $table->index('sharepoint_drive_item_id');
        });

        DB::table('documents')->where('is_active', true)->update(['status' => 'published', 'published_at' => DB::raw('created_at')]);
        DB::table('documents')->where('is_active', false)->update(['status' => 'hidden']);

        Schema::table('documents', function (Blueprint $table): void {
            $table->dropColumn('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table): void {
            $table->boolean('is_active')->default(true)->after('sharepoint_permission_id');
        });

        DB::table('documents')->where('status', '!=', 'published')->update(['is_active' => false]);
        // Rows that only discovery created were never documents before this migration.
        DB::table('documents')->where('status', 'pending')->delete();

        Schema::table('documents', function (Blueprint $table): void {
            $table->dropForeign(['published_by']);
            $table->dropIndex(['status']);
            $table->dropIndex(['sharepoint_drive_item_id']);
            $table->dropColumn([
                'status', 'removed_from_sharepoint_at', 'published_at', 'published_by',
                'sharepoint_drive_item_id', 'sharepoint_path', 'sharepoint_web_url',
                'sharepoint_modified_at', 'sharepoint_institution_label',
            ]);
        });
    }
};
