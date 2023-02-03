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
        // This is to ensure, that when creating folders in sharepoint, they would be unique.
        // Since sharepoint does not allow folders with the same name in the same folder, we need to ensure that
        // the folder name is unique in the database.
        Schema::table('doings', function (Blueprint $table) {
            $table->string('drive_item_name')->nullable()->after('title')->comment('The name of the folder in the Sharepoint drive');
            $table->unique(['drive_item_name']);
        });


        Schema::table('duties', function (Blueprint $table) {
            $table->unique(['name', 'institution_id', 'email']);
        });

        Schema::table('goals', function (Blueprint $table) {
            $table->unique(['title', 'padalinys_id']);
        });

        Schema::table('institutions', function (Blueprint $table) {
            $table->unique(['name', 'padalinys_id']);
        });

        Schema::table('meetings', function (Blueprint $table) {
            $table->string('title')->nullable(false)->after('id');
        });

        Schema::table('types', function (Blueprint $table) {
            $table->unique(['title', 'model_type']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Schema::table('doings', function (Blueprint $table) {
        //     $table->dropUnique(['drive_item_name']);
        //     $table->dropColumn('drive_item_name');
        // });

        Schema::table('duties', function (Blueprint $table) {
            $table->dropUnique(['name', 'institution_id']);
        });

        Schema::table('goals', function (Blueprint $table) {
            $table->dropUnique(['title', 'padalinys_id']);
        });

        Schema::table('institutions', function (Blueprint $table) {
            $table->dropUnique(['name', 'padalinys_id']);
        });

        Schema::table('meetings', function (Blueprint $table) {
            $table->dropColumn('title');
        });

        Schema::table('types', function (Blueprint $table) {
            $table->dropUnique(['title', 'model_type']);
        });
    }
};
