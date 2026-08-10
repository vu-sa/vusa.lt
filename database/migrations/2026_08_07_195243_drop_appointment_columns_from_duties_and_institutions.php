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
        Schema::table('duties', function (Blueprint $table): void {
            $table->dropColumn(['selection_method', 'appointed_by', 'term_length', 'responsibilities']);
        });

        Schema::table('institutions', function (Blueprint $table): void {
            $table->dropColumn(['selection_method', 'appointed_by', 'term_length']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('duties', function (Blueprint $table): void {
            $table->string('selection_method')->nullable()->after('contacts_grouping');
            $table->json('appointed_by')->nullable()->after('selection_method');
            $table->json('term_length')->nullable()->after('appointed_by');
            $table->json('responsibilities')->nullable()->after('term_length');
        });

        Schema::table('institutions', function (Blueprint $table): void {
            $table->string('selection_method')->nullable()->after('meeting_periodicity_days');
            $table->json('appointed_by')->nullable()->after('selection_method');
            $table->json('term_length')->nullable()->after('appointed_by');
        });
    }
};
