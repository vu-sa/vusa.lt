<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // rename sharepoint documents to files
        Schema::rename('sharepoint_documents', 'sharepoint_files');

        // create sharepoint fileables
        Schema::create('sharepoint_fileables', function (Blueprint $table) {
            $table->foreignUuid('sharepoint_file_id')->constrained()->cascadeOnDelete();
            $table->ulidMorphs('fileable');
            $table->unique(['sharepoint_file_id', 'fileable_id', 'fileable_type'], 'sharepoint_fileables_unique');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        // remove all files from sharepoint_files
        DB::table('sharepoint_files')->delete();

        Schema::table('sharepoint_files', function (Blueprint $table) {
            $table->dropColumn('documentable_id');
            $table->dropColumn('documentable_type');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
};
