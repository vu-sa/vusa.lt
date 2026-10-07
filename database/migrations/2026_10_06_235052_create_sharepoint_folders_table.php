<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The archive drive's folder tree, so document paths are rebuilt from ids: Graph's delta feed omits
 * parentReference.path and does not re-send the contents of a renamed or moved folder.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sharepoint_folders', function (Blueprint $table): void {
            $table->string('drive_item_id', 100)->primary();
            $table->string('parent_id', 100)->nullable()->index();
            // Empty for the drive root.
            $table->string('name', 400);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sharepoint_folders');
    }
};
