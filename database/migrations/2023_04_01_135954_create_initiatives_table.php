<?php

use App\Actions\CreateModelPermissions;
use App\Models\Initiative;
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
        Schema::create('initiatives', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->unsignedInteger('padalinys_id')->nullable();
            $table->foreign('padalinys_id')->references('id')->on('padaliniai');
            $table->json('title');
            $table->string('slug')->unique();
            $table->json('description')->nullable();
            $table->string('participation_url')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('logo')->nullable();
            $table->string('cover')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->softDeletes();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        // seed permissions
        // check if permissions table has initiatives
        if (Schema::hasTable('permissions')) {
            // check if exists permission with name = 'initiatives.read.padalinys'
            if (! DB::table('permissions')->where('name', 'initiatives.read.padalinys')->exists()) {
                // seed permissions
                CreateModelPermissions::execute(['initiative']);
            }
        }

        // seed initiative types
        if (Schema::hasTable('types')) {
            DB::table('types')->insertOrIgnore([
                [
                    'title' => 'VU SA programos, klubai, projektai',
                    'model_type' => Initiative::class,
                    'slug' => 'pkp',
                ],
                [
                    'title' => 'VU SA iniciatyvos',
                    'model_type' => Initiative::class,
                    'slug' => 'vu-sa-iniciatyvos',
                ],
                [
                    'title' => 'Studentų iniciatyvos',
                    'model_type' => Initiative::class,
                    'slug' => 'studentu-iniciatyvos',
                ],
                [
                    'title' => 'Renginių ciklai',
                    'model_type' => Initiative::class,
                    'slug' => 'renginiu-ciklas',
                ],
                [
                    'title' => 'Darbo grupės',
                    'model_type' => Initiative::class,
                    'slug' => 'darbo-grupe',
                ],
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('initiatives');
    }
};
