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
        Schema::create('reservation_resource', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->char('reservation_id', 26)->index('reservation_resource_reservation_id_foreign');
            $table->char('resource_id', 26)->index('reservation_resource_resource_id_foreign');
            $table->dateTime('start_time')->nullable();
            $table->dateTime('end_time')->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->string('state')->default('created');
            $table->dateTime('returned_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('reservation_resource');
    }
};
