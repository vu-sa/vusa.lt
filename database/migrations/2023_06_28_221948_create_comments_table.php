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
        Schema::create('comments', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            $table->char('parent_id', 26)->nullable();
            $table->text('comment');
            $table->string('decision')->nullable()->comment('The decision made alongside the comment.');
            $table->char('user_id', 26)->index('comments_user_id_foreign');
            $table->string('commentable_type');
            $table->char('commentable_id', 36);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
            $table->softDeletes();

            $table->index(['commentable_type', 'commentable_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('comments');
    }
};
