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
        Schema::create('page_views', function (Blueprint $table) {
            $table->increments("id");
            $table->string("host");
            $table->string("url");
            $table->unsignedBigInteger('company_id')->nullable();
            $table->string("session_id");
            $table->unsignedInteger('user_id')->nullable();//here note to make it nullable if your page is accessible publically as well not only by logged in users. Also its more appropriate to have "unsignedInteger" type instead of "string" type as mentioned in Jean Marcos' answer because user_id will save same data as id field of users table which in most cases will be an auto incremented id.
            $table->string("ip");
            $table->text("agent")->nullable()->change();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('page_views');
    }
};
