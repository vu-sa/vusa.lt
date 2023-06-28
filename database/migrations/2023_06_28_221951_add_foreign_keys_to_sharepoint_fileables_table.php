<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
        Schema::table('sharepoint_fileables', function (Blueprint $table) {
            $table->foreign(['sharepoint_file_id'])->references(['id'])->on('sharepoint_files')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sharepoint_fileables', function (Blueprint $table) {
            $table->dropForeign('sharepoint_fileables_sharepoint_file_id_foreign');
        });
    }
};
