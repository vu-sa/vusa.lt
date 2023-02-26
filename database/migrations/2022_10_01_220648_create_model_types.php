<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
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
        // check if duties_institutions_types table is empty
        if (DB::table('duties_institutions_types')->count() === 0) {
            Artisan::call('db:seed', [
                '--class' => 'InstitutionTypesSeeder',
            ]);
        }

        // check if duties_types table is empty
        if (DB::table('duties_types')->count() === 0) {
            Artisan::call('db:seed', [
                '--class' => 'DutyTypesSeeder',
            ]);
        }

        Schema::create('types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('types')->nullOnDelete();
            $table->string('title')->nullable();
            $table->string('model_type')->nullable();
            $table->string('description')->nullable();
            $table->string('slug')->nullable();
            $table->json('extra_attributes')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('types');
    }
};
