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
        Schema::create('calendar', function (Blueprint $table) {
            $table->increments('id');
            $table->dateTime('date');
            $table->dateTime('end_date')->nullable();
            $table->text('title');
            $table->text('description')->nullable();
            $table->string('location', 255)->nullable();
            $table->string('category', 255)->nullable()->index('calendar_category_foreign');
            $table->mediumText('url')->nullable();
            $table->unsignedInteger('padalinys_id')->default(16)->index('calendar_padalinys_id_foreign');
            $table->longText('extra_attributes')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
            $table->unsignedBigInteger('registration_form_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('calendar');
    }
};
