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
        Schema::table('duties', function (Blueprint $table) {
            // add order column
            $table->unsignedInteger('order')->default(0)->after('institution_id')->comment('Order of duty in institution');
        });
    }
};
