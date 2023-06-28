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
        Schema::create('typeables', function (Blueprint $table) {
            $table->unsignedBigInteger('type_id');
            $table->string('typeable_type');
            $table->char('typeable_id', 26);

            $table->index(['typeable_type', 'typeable_id']);
            $table->unique(['type_id', 'typeable_id', 'typeable_type']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('typeables');
    }
};
