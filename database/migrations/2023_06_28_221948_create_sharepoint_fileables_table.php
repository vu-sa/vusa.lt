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
        Schema::create('sharepoint_fileables', function (Blueprint $table) {
            $table->char('sharepoint_file_id', 36);
            $table->string('fileable_type');
            $table->char('fileable_id', 26);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();

            $table->index(['fileable_type', 'fileable_id']);
            $table->unique(['sharepoint_file_id', 'fileable_id', 'fileable_type'], 'sharepoint_fileables_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sharepoint_fileables');
    }
};
