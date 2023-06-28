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
        Schema::create('resources', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->json('name');
            $table->json('description')->nullable();
            $table->string('location')->nullable();
            $table->unsignedInteger('capacity')->default(1);
            $table->unsignedInteger('padalinys_id')->index('resources_padalinys_id_foreign');
            $table->boolean('is_reservable')->default(true);
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
        Schema::dropIfExists('resources');
    }
};
