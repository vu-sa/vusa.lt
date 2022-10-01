<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
        Schema::create('types', function (Blueprint $table) {
            $table->id();
            // parent id 
            $table->foreignId('parent_id')->nullable()->constrained('types')->nullOnDelete();
            $table->morphs('typeable');
            // description column
            $table->string('description')->nullable();
            // slug column
            $table->string('slug')->nullable();
            // extra attributes
            $table->json('extra_attributes')->nullable();
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
        Schema::dropIfExists('model_types');
    }
};
