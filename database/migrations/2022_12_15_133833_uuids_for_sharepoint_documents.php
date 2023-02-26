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
        Schema::table('sharepoint_documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->dropPrimary('sharepoint_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sharepoint_documents', function (Blueprint $table) {
            $table->dropColumn('uuid');
            $table->bigIncrements('sharepoint_id')->primary()->change();
        });
    }
};
