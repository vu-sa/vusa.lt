<?php

use App\Models\Permission;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /** ModelPermissionSeeder only adds permissions when it is re-run, so these are created here. */
    private const array PERMISSIONS = [
        'mailingLists.create.padalinys',
        'mailingLists.create.*',
        'mailingLists.read.padalinys',
        'mailingLists.read.*',
        'mailingLists.update.padalinys',
        'mailingLists.update.*',
        'mailingLists.delete.padalinys',
        'mailingLists.delete.*',
    ];

    public function up(): void
    {
        Schema::create('mailing_lists', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->unsignedInteger('tenant_id');
            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->string('address')->unique();
            $table->string('mailman_list_id')->nullable()->unique();
            $table->string('display_name');
            $table->text('description')->nullable();
            $table->string('mode')->default('unmanaged')->index();
            $table->string('archive_policy')->nullable();
            $table->unsignedInteger('member_count')->default(0);
            $table->unsignedInteger('posts_last_year')->default(0);
            $table->timestamp('last_post_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->string('last_sync_status')->nullable();
            $table->text('last_sync_error')->nullable();
            $table->timestamp('archive_fetched_at')->nullable();
            $table->timestamps();
        });

        Schema::create('mailing_list_sources', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('mailing_list_id')->constrained('mailing_lists')->cascadeOnDelete();
            $table->string('type');
            // A duty/institution/list ulid or a type's integer id, depending on `type`.
            $table->string('source_id', 26)->nullable();
            $table->json('filters')->nullable();
            $table->string('address_mode')->default('holder');
            $table->string('email')->nullable();
            $table->string('display_name')->nullable();
            $table->string('note')->nullable();
            $table->date('expires_on')->nullable();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['type', 'source_id']);
        });

        Schema::create('mailing_list_exclusions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('mailing_list_id')->constrained('mailing_lists')->cascadeOnDelete();
            $table->string('email');
            $table->string('note')->nullable();
            $table->foreignUlid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['mailing_list_id', 'email']);
        });

        Schema::create('mailing_list_members', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('mailing_list_id')->constrained('mailing_lists')->cascadeOnDelete();
            $table->string('email');
            $table->string('display_name')->nullable();
            $table->foreignUlid('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('reasons')->nullable();
            $table->timestamps();
            $table->unique(['mailing_list_id', 'email']);
            $table->index('email');
        });

        Schema::create('mailing_list_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('mailing_list_id')->constrained('mailing_lists')->cascadeOnDelete();
            $table->string('message_id_hash');
            $table->string('thread_id')->nullable();
            $table->string('subject')->nullable();
            $table->string('sender_name')->nullable();
            $table->string('sender_email')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->longText('body_text')->nullable();
            $table->json('attachments')->nullable();
            $table->string('url', 2048)->nullable();
            $table->timestamps();
            $table->unique(['mailing_list_id', 'message_id_hash']);
            $table->index(['mailing_list_id', 'sent_at']);
        });

        foreach (self::PERMISSIONS as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Schema::dropIfExists('mailing_list_messages');
        Schema::dropIfExists('mailing_list_members');
        Schema::dropIfExists('mailing_list_exclusions');
        Schema::dropIfExists('mailing_list_sources');
        Schema::dropIfExists('mailing_lists');

        Permission::query()->whereIn('name', self::PERMISSIONS)->get()->each->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
