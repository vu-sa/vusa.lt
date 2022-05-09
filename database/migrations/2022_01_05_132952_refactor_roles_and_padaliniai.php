<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class RefactorRolesAndPadaliniai extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('banners', function (Blueprint $table) {
            $table->dropForeign('banners_role_id_foreign');
            $table->boolean('updated')->default(false);
        });
        
        Schema::table('duties_institutions', function (Blueprint $table) {
            $table->dropForeign('duties_institutions_role_id_foreign');
            $table->boolean('updated')->default(false);
        });

        Schema::table('main_page', function (Blueprint $table) {
            $table->dropForeign('main_page_role_id_foreign');
            $table->boolean('updated')->default(false);
        });

        Schema::table('news', function (Blueprint $table) {
            $table->dropForeign('news_role_id_foreign');
            $table->boolean('updated')->default(false);
        });

        Schema::table('pages', function (Blueprint $table) {
            $table->dropForeign('pages_role_id_foreign');
            $table->boolean('updated')->default(false);
        });
        
        $padaliniai = DB::table('padaliniai')->select('id', 'shortname', 'alias')->get();

        foreach ($padaliniai as $key => $value) {
            if ($value->shortname == 'VU SA') {
                $role_id = 1;
            }
            else {
                $role = DB::table('roles')->select('id')->where('alias', '=', 'vusa' . $value->alias)->first();
                
                if (is_null($role)) {
                    continue;
                }
                else {
                    $role_id = $role->id;
                }
            }

            DB::table('banners')->where('role_id', '=', $role_id)->where('updated', '=', false)->update(['role_id' => $value->id, 'updated' => true]);
            DB::table('duties_institutions')->where('role_id', '=', $role_id)->where('updated', '=', false)->update(['role_id' => $value->id, 'updated' => true]);
            DB::table('main_page')->where('role_id', '=', $role_id)->where('updated', '=', false)->update(['role_id' => $value->id, 'updated' => true]);
            DB::table('news')->where('role_id', '=', $role_id)->where('updated', '=', false)->update(['role_id' => $value->id, 'updated' => true]);
            DB::table('pages')->where('role_id', '=', $role_id)->where('updated', '=', false)->update(['role_id' => $value->id, 'updated' => true]);
            DB::table('roles')->where('id', '=', $role_id)->delete();
        }

        DB::table('banners')->where('role_id', '=', 12)->where('updated', '=', false)->update(['role_id' => 6, 'updated' => true]);
        DB::table('duties_institutions')->where('role_id', '=', 12)->where('updated', '=', false)->update(['role_id' => 6, 'updated' => true]);
        DB::table('main_page')->where('role_id', '=', 12)->where('updated', '=', false)->update(['role_id' => 6, 'updated' => true]);
        DB::table('news')->where('role_id', '=', 12)->where('updated', '=', false)->update(['role_id' => 6, 'updated' => true]);
        DB::table('pages')->where('role_id', '=', 12)->where('updated', '=', false)->update(['role_id' => 6, 'updated' => true]);
        DB::table('roles')->where('id', '=', 12)->delete();

        Schema::table('banners', function (Blueprint $table) {
            $table->renameColumn('role_id', 'padalinys_id');
            $table->dropColumn('updated');
        });

        Schema::table('duties_institutions', function (Blueprint $table) {
            $table->renameColumn('role_id', 'padalinys_id');
            $table->dropColumn('updated');
        });

        Schema::table('main_page', function (Blueprint $table) {
            $table->renameColumn('role_id', 'padalinys_id');
            $table->dropColumn('updated');
        });

        Schema::table('news', function (Blueprint $table) {
            $table->renameColumn('role_id', 'padalinys_id');
            $table->dropColumn('updated');
        });

        Schema::table('pages', function (Blueprint $table) {
            $table->renameColumn('role_id', 'padalinys_id');
            $table->dropColumn('updated');
        });

        Schema::table('banners', function (Blueprint $table) {
            $table->foreign('padalinys_id')->references('id')->on('padaliniai');
        });

        Schema::table('duties_institutions', function (Blueprint $table) {
            $table->foreign('padalinys_id')->references('id')->on('padaliniai');
        });

        Schema::table('main_page', function (Blueprint $table) {
            $table->foreign('padalinys_id')->references('id')->on('padaliniai');
        });

        Schema::table('news', function (Blueprint $table) {
            $table->foreign('padalinys_id')->references('id')->on('padaliniai');
        });

        Schema::table('pages', function (Blueprint $table) {
            $table->foreign('padalinys_id')->references('id')->on('padaliniai');
        });
    } 

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
