<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cadences', function (Blueprint $table) {
            $table->char('id', 26)->primary();
            // NULL = the global ladder every institution inherits when it has no override.
            $table->char('institution_id', 26)->nullable();
            $table->date('start_date');
            // NOT NULL, unlike dutiables.end_date — a cadence always has both ends.
            $table->date('end_date');
            $table->timestamps();

            $table->foreign('institution_id')->references('id')->on('institutions')->cascadeOnDelete();
            // MySQL treats NULLs as distinct, so this does not constrain global rows —
            // CadenceRequest carries the whereNull('institution_id') uniqueness rule.
            $table->unique(['institution_id', 'start_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cadences');
    }
};
