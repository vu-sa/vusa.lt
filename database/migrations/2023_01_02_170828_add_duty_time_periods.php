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
        Schema::table('duties_users', function (Blueprint $table) {
            $table->date('start_date')->default('2022-06-01')->after('user_id');
            $table->date('end_date')->nullable()->after('start_date');
        });

        // for all duty user records, set the start date to the 2022-06-01

        Schema::table('duties_users', function (Blueprint $table) {
            $table->date('start_date')->default(null)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('duties_users', function (Blueprint $table) {
            $table->dropColumn('start_date');
            $table->dropColumn('end_date');
        });
    }
};
