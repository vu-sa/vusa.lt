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
        Schema::create('relationships', function (Blueprint $table) {
            $table->id();
            // add type, description, etc.
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('type')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        Schema::create('relationshipables', function (Blueprint $table) {
            $table->foreignId('relationship_id')->constrained();
            $table->morphs('relationshipable', 'relationshipable_id_index');
            // add second relationshipable id
            $table->bigInteger('related_model_id')->index('related_model_id_index');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('relationshipables');
        Schema::dropIfExists('relationships');
    }
};
