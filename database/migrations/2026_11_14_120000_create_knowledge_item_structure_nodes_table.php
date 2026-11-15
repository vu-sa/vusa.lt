<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('knowledge_item_structure_nodes', function (Blueprint $table): void {
            $table->id();
            $table->foreignUlid('knowledge_item_id')->constrained()->cascadeOnDelete();
            $table->string('graph', 40);
            $table->string('node', 100)->nullable();
            $table->unsignedSmallInteger('order')->default(0);
            $table->timestamps();
            // One place per graph for an article, and one article per node; a graph without a node may repeat.
            $table->unique(['knowledge_item_id', 'graph']);
            $table->unique(['graph', 'node']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_item_structure_nodes');
    }
};
