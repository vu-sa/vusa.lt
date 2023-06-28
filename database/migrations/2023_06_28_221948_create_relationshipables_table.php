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
        Schema::create('relationshipables', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('relationship_id')->index('relationshipables_relationship_id_foreign');
            $table->string('relationshipable_type');
            $table->string('relationshipable_id', 26);
            $table->string('related_model_id', 26)->index('related_model_id_index');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();

            $table->index(['relationshipable_type', 'relationshipable_id'], 'relationshipable_id_index');
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
    }
};
