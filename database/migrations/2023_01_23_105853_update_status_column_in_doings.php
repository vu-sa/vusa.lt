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
        Schema::table('doings', function (Blueprint $table) {
            // rename status to state
            $table->renameColumn('status', 'state');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('doings', function (Blueprint $table) {
            // rename state to status
            $table->renameColumn('state', 'status');
        });
    }
};
