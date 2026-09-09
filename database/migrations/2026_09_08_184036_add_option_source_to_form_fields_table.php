<?php

use App\Models\Institution;
use App\Models\Tenant;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('form_fields', function (Blueprint $table) {
            $table->string('option_source')->nullable()->after('use_model_options');
        });

        DB::table('form_fields')
            ->where('use_model_options', true)
            ->where('options_model', Tenant::class)
            ->update(['option_source' => 'tenant']);

        DB::table('form_fields')
            ->where('use_model_options', true)
            ->where('options_model', Institution::class)
            ->update(['option_source' => 'institution']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('form_fields', function (Blueprint $table) {
            $table->dropColumn('option_source');
        });
    }
};
