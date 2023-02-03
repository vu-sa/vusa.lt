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
            $table->dropForeign(['doing_type_id']);
        });
        
        Schema::table('doings', function (Blueprint $table) {
            $table->dropColumn('doing_type_id');
        });

        Schema::table('duties', function (Blueprint $table) {
            $table->dropForeign(['type_id']);
        });

        Schema::table('duties', function (Blueprint $table) {
            $table->dropColumn('type_id');
        });

        Schema::table('duties_institutions', function (Blueprint $table) {
            $table->dropForeign(['type_id']);
        });
        
        Schema::table('duties_institutions', function (Blueprint $table) {
            $table->dropColumn('type_id');
        });

        Schema::dropIfExists('doing_types');
        Schema::dropIfExists('duties_types');
        Schema::dropIfExists('duties_institutions_types');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::create('doing_types', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->timestamps();
        });

        Schema::create('duties_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('alias');
            $table->string('description');
            $table->unsignedBigInteger('pid')->nullable();
            $table->timestamps();
        });

        Schema::create('duties_institutions_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('alias');
            $table->string('description');
            $table->string('attributes');
            $table->timestamps();
        });

        Schema::table('doings', function (Blueprint $table) {
            $table->unsignedBigInteger('doing_type_id')->nullable();
            $table->foreign('doing_type_id')->references('id')->on('doing_types');
        });

        Schema::table('duties', function (Blueprint $table) {
            $table->unsignedBigInteger('type_id')->nullable();
            $table->foreign('type_id')->references('id')->on('duties_types');
        });

        Schema::table('duties_institutions', function (Blueprint $table) {
            $table->unsignedBigInteger('type_id')->nullable();
            $table->foreign('type_id')->references('id')->on('duties_institutions_types');
        });
    }
};
