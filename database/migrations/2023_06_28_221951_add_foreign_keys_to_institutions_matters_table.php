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
        Schema::table('institutions_matters', function (Blueprint $table) {
            $table->foreign(['matter_id'])->references(['id'])->on('matters')->onDelete('CASCADE');
            $table->foreign(['institution_id'])->references(['id'])->on('institutions')->onDelete('CASCADE');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('institutions_matters', function (Blueprint $table) {
            $table->dropForeign('institutions_matters_matter_id_foreign');
            $table->dropForeign('institutions_matters_institution_id_foreign');
        });
    }
};
