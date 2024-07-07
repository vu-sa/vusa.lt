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
