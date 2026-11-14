<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('knowledge_items', function (Blueprint $table): void {
            $table->string('structure_graph', 40)->nullable()->after('description');
            $table->string('structure_node', 100)->nullable()->after('structure_graph');
            // One article per node, so a node links to exactly one article; a graph without a node may repeat.
            $table->unique(['structure_graph', 'structure_node']);
        });
    }

    public function down(): void
    {
        Schema::table('knowledge_items', function (Blueprint $table): void {
            $table->dropUnique(['structure_graph', 'structure_node']);
            $table->dropColumn(['structure_graph', 'structure_node']);
        });
    }
};
