<?php

use App\Models\Doing;
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
        Schema::create('doing_question', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doing_id')->constrained()->onDelete('cascade');
            $table->foreignId('question_id')->constrained()->onDelete('cascade');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        Schema::table('doing_question', function (Blueprint $table) {
            $table->unique(['doing_id', 'question_id']);
        });

        Doing::withTrashed()->get()->each(function (Doing $doing) {
            $doing->questions()->attach($doing->question_id);
        });

        Schema::table('doings', function (Blueprint $table) {
            $table->dropForeign(['question_id']);
            $table->dropColumn('question_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('doing_question');
    }
};
