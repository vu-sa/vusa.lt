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
        Schema::create('users', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->string('email', 255)->unique();
            $table->string('phone', 255)->nullable();
            $table->string('name', 255);
            $table->string('password', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('email_verified_at')->nullable();
            $table->string('remember_token', 60)->nullable();
            $table->timestamp('last_action')->nullable();
            $table->dateTime('last_changelog_check')->nullable();
            $table->text('microsoft_token')->nullable();
            $table->string('google_token', 255)->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->string('profile_photo_path', 2048)->nullable();
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
        Schema::dropIfExists('users');
    }
};
