<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::rename('institution_matters', 'matters');
        
        Schema::create('institutions_matters', function (Blueprint $table) {
            $table->foreignUlid('institution_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('matter_id')->constrained()->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        DB::table('institutions_matters')->insertUsing(
            ['matter_id', 'institution_id'],
            DB::table('matters')->select('id', 'institution_id')
        );

        Schema::table('matters', function (Blueprint $table) {
            $table->dropForeign('institution_matters_institution_id_foreign');
            $table->dropColumn('institution_id');
        });

        // make composite primary key
        Schema::table('institutions_matters', function (Blueprint $table) {
            $table->primary(['institution_id', 'matter_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('many', function (Blueprint $table) {
            //
        });
    }
};
