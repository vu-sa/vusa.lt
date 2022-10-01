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
        Schema::create('model_has_content_types', function (Blueprint $table) {
            $table->foreignId('content_type_id')->constrained('content_types')->cascadeOnDelete();
            $table->morphs('model');
            // unique index
            $table->unique(['content_type_id', 'model_id', 'model_type'], 'model_has_content_types_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('model_has_content_types');
    }
};
