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
        Schema::table('forms', function (Blueprint $table) {
            // Never implemented: the column shipped with a TODO and nothing ever wrote to it.
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });

        Schema::table('form_fields', function (Blueprint $table) {
            $table->index(['form_id', 'order'], 'form_fields_form_id_order_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('form_fields', function (Blueprint $table) {
            $table->dropIndex('form_fields_form_id_order_index');
        });

        Schema::table('forms', function (Blueprint $table) {
            $table->foreignUlid('user_id')->nullable()->constrained()->nullOnDelete();
        });
    }
};
