<?php

use App\Actions\CreateModelPermissions;
use App\Models\Organization;
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
        Schema::create('organizations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->unsignedInteger('padalinys_id')->nullable();
            $table->foreign('padalinys_id')->references('id')->on('padaliniai');
            $table->json('title');
            $table->string('slug')->unique();
            $table->json('description')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('logo')->nullable();
            $table->string('cover')->nullable();
            $table->softDeletes();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        // check if permissions table has organizations
        if (Schema::hasTable('permissions')) {
            // check if exists permission with name = 'organizations.read.padalinys'
            if (! DB::table('permissions')->where('name', 'organizations.read.padalinys')->exists()) {
                // seed permissions
                CreateModelPermissions::execute(['organization']);
            }
        }

        // seed organization types
        if (Schema::hasTable('types')) {
            DB::table('types')->insertOrIgnore([
                [
                    'title' => 'VU SA programos, klubai, projektai',
                    'model_type' => Organization::class,
                    'slug' => 'pkp',
                ],
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organizations');

        // check if permissions table has organizations
        if (Schema::hasTable('permissions')) {
            // check if exists permission with name = 'organizations.read.padalinys'
            if (DB::table('permissions')->where('name', 'organizations.read.padalinys')->exists()) {
                // delete permissions
                DB::table('permissions')->where('name', 'like', 'organizations.%')->delete();
            }
        }
    }
};
