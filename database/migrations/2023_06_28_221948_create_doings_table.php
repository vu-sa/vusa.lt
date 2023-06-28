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
        Schema::create('doings', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->string('title');
            $table->string('drive_item_name')->nullable()->unique()->comment('The name of the folder in the Sharepoint drive');
            $table->string('state')->index('doings_status_index');
            $table->dateTime('date');
            $table->json('extra_attributes')->nullable();
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
        Schema::dropIfExists('doings');
    }
};
