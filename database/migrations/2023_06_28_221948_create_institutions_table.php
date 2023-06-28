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
        Schema::create('institutions', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('parent_id', 26)->nullable();
            $table->string('name', 255)->nullable();
            $table->string('short_name', 255)->nullable();
            $table->string('alias', 255);
            $table->text('description')->nullable();
            $table->string('image_url', 255)->nullable();
            $table->unsignedInteger('padalinys_id')->nullable()->index('duties_institutions_padalinys_id_foreign');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
            $table->longText('extra_attributes')->nullable();
            $table->softDeletes();

            $table->unique(['name', 'padalinys_id']);
            $table->unique(['parent_id', 'alias']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('institutions');
    }
};
