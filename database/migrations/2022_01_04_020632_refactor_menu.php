<?php

use App\Models\Navigation;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RefactorMenu extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('navigation', function (Blueprint $table) {
            $table->increments('id')->change();
            $table->renameColumn('pid', 'parent_id');
            $table->renameColumn('text', 'name');
            $table->renameColumn('show', 'is_active');
            $table->unsignedInteger('pid')->default(0)->change();
            $table->string('url')->nullable(false)->change();
            $table->dropColumn('readonly');
            $table->dropColumn('creator');
            $table->dropColumn('creator_time');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        Schema::table('navigation', function (Blueprint $table) {
            $table->unsignedInteger('user_id')->nullable()->after('parent_id');
            $table->foreign('user_id')->references('id')->on('users');
            $table->unsignedInteger('padalinys_id')->after('user_id')->default(16);
            $table->foreign('padalinys_id')->references('id')->on('padaliniai');
            // TODO: need to set unique
            // $table->unique(['parent_id', 'lang', 'order', 'padalinys_id']);
        });

        // delete navigation item "padaliniai" and all of its children from navigation table
        $padaliniai_menu_item = DB::table('navigation')->where('name', 'Padaliniai')->get()->first();

        DB::table('navigation')->where('parent_id', $padaliniai_menu_item->id)->delete();
        DB::table('navigation')->where('id', $padaliniai_menu_item->id)->delete();
        DB::table('navigation')->where('id', 106)->delete();
        
        // delete all children of kontaktai

        $kontaktai_menu_item = DB::table('navigation')->where('name', 'Kontaktai')->get()->first();
        DB::table('navigation')->where('parent_id', $kontaktai_menu_item->id)->delete();
        DB::table('navigation')->where('id', $kontaktai_menu_item->id)->delete();

        // remove leading slashes from table navigation url columns in every row value
        Navigation::all()->each(function ($item) {
            $item->url = ltrim($item->url, '/');
            $item->save();
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
