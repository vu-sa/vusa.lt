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
        Schema::create('saziningai_observers', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('exam_uuid', 30)->index('saziningai_observers_exam_uuid_foreign');
            $table->string('name', 100);
            $table->string('email', 255)->nullable();
            $table->string('phone', 100);
            $table->unsignedInteger('flow')->index('saziningai_observers_flow_foreign');
            $table->dateTime('created_at')->useCurrent();
            $table->string('has_arrived', 11);
            $table->string('phone_p', 255)->nullable();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
            $table->unsignedInteger('padalinys_id')->default(16)->index('saziningai_observers_padalinys_id_foreign');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('saziningai_observers');
    }
};
