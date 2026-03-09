<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('fileable_files', function (Blueprint $table) {
            $table->ulid('id')->primary();

            // Polymorphic relationship to fileable models (Meeting, Institution, Duty, Type)
            $table->string('fileable_type');
            $table->char('fileable_id', 26); // ULID

            // SharePoint reference
            $table->string('sharepoint_id')->comment('SharePoint drive item ID');
            $table->text('sharepoint_path')->nullable()->comment('Canonical path in SharePoint');

            // File metadata (stored locally to avoid API calls)
            $table->string('name')->comment('Original filename');
            $table->string('file_type')->nullable()->comment('Semantic type: protokolas, ataskaita, etc.');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->timestamp('file_date')->nullable()->comment('Document date (not upload date)');
            $table->text('description')->nullable();

            // Public sharing
            $table->string('public_link')->nullable()->comment('SharePoint anonymous sharing link');
            $table->timestamp('public_link_expires_at')->nullable();

            // Sync status
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamp('deleted_externally_at')->nullable()->comment('Set when file deleted in SharePoint');

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['fileable_type', 'fileable_id']);
            $table->index('sharepoint_id');
            $table->index('file_type');
            $table->index('deleted_externally_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fileable_files');
    }
};
