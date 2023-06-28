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
        Schema::create('pages', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title', 200);
            $table->string('permalink', 200)->nullable();
            $table->mediumText('text');
            $table->string('lang', 2)->default('lt');
            $table->unsignedInteger('other_lang_id')->nullable()->unique();
            $table->unsignedInteger('category_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('padalinys_id')->index('pages_padalinys_id_foreign');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();

            $table->index(['other_lang_id']);
            $table->unique(['permalink', 'padalinys_id'], 'pages_permalink_role_id_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pages');
    }
};
