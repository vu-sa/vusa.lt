<?php

use App\Models\Pivots\Dutiable;
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
        Schema::table('dutiables', function (Blueprint $table) {
            // add ulid and make it first column
            $table->ulid('id')->first();
        });

        // add ulid to every existing dutiable
        DB::table('dutiables')->get()->each(function ($dutiable) {
            $ulid = strtolower(\Illuminate\Support\Str::ulid());
            DB::table('dutiables')->where('duty_id', $dutiable->duty_id)->where('dutiable_id', $dutiable->dutiable_id)->where('start_date', $dutiable->start_date)->update(['id' => $ulid]);
        });

        // remove old primary key
        Schema::table('dutiables', function (Blueprint $table) {
            $table->dropPrimary(['dutiable_id', 'dutiable_type', 'duty_id', 'start_date']);

            // add new primary key
            $table->primary('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dutiables', function (Blueprint $table) {
            $table->dropColumn('id');
        });
    }
};
