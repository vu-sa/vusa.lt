<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agenda_item_problem', function (Blueprint $table) {
            $table->foreignUlid('agenda_item_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('problem_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->primary(['agenda_item_id', 'problem_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agenda_item_problem');
    }
};
