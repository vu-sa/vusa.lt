<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // remove foreign keys
        
        Schema::table('saziningai_observers', function (Blueprint $table) {
            $table->dropForeign(['exam_uuid']);
            $table->dropForeign(['flow']);
        });

        Schema::table('saziningai_exam_flows', function (Blueprint $table) {
            $table->dropForeign(['exam_uuid']);
        });

        Schema::table('saziningai_exams', function (Blueprint $table) {
            $table->dropForeign(['padalinys_id']);
        });

        // drop tables

        Schema::dropIfExists('saziningai_observers');
        Schema::dropIfExists('saziningai_exam_flows');
        Schema::dropIfExists('saziningai_exams');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
