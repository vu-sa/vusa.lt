<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Appointment metadata describes how members of an institution / a duty are
     * selected. It lives on the institution as defaults, with optional per-duty
     * overrides. Responsibilities are duty-specific.
     */
    public function up(): void
    {
        Schema::table('institutions', function (Blueprint $table) {
            $table->string('selection_method')->nullable()->after('contacts_layout');
            $table->json('appointed_by')->nullable()->after('selection_method');
            $table->json('term_length')->nullable()->after('appointed_by');
        });

        Schema::table('duties', function (Blueprint $table) {
            $table->string('selection_method')->nullable()->after('places_to_occupy');
            $table->json('appointed_by')->nullable()->after('selection_method');
            $table->json('term_length')->nullable()->after('appointed_by');
            $table->json('responsibilities')->nullable()->after('term_length');
        });
    }

    public function down(): void
    {
        Schema::table('institutions', function (Blueprint $table) {
            $table->dropColumn(['selection_method', 'appointed_by', 'term_length']);
        });

        Schema::table('duties', function (Blueprint $table) {
            $table->dropColumn(['selection_method', 'appointed_by', 'term_length', 'responsibilities']);
        });
    }
};
