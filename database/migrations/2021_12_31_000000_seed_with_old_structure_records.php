<?php

use App\Models\News;
use App\Models\Page;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class SeedWithOldStructureRecords extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (config('app.env') == 'local') {
            DB::unprepared(file_get_contents('database/vusa_www-n.sql'));
        }
        
        // Prerequisites
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('created');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('email_verified_at')->nullable()->after('gid');
        });

        Schema::rename('saziningai', 'saziningai_exams');
        Schema::rename('saziningai_people', 'saziningai_observers');
        Schema::rename('sidebar', 'banners');
        Schema::rename('mainPage', 'main_page');
        Schema::rename('menu_new', 'navigation');
        Schema::rename('page', 'pages');

        Schema::table('saziningai_exams', function (Blueprint $table) {
            $table->dropColumn('registration_time');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        Schema::table('saziningai_observers', function (Blueprint $table) {
            $table->renameColumn('dateRegistered', 'created_at');
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        Schema::table('calendar', function (Blueprint $table) {
            $table->dropColumn('editor_time');
        });

        Schema::table('calendar', function (Blueprint $table) {
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });


        Schema::table('banners', function (Blueprint $table) {
            $table->dropColumn('editor_time');
        });

        Schema::table('banners', function (Blueprint $table) {
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        Schema::table('main_page', function (Blueprint $table) {
            $table->dropColumn('created_time');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        Schema::table('pages', function (Blueprint $table) {
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        Schema::table('news', function (Blueprint $table) {
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        // update news created_at with editor_time

        $news = News::all();

        foreach ($news as $key => $value) {
            $value->created_at = $value->editor_time;
            $value->save();
        }

        // update pages created_at with editor_time

        $pages = Page::all();

        foreach ($pages as $key => $value) {
            $value->created_at = $value->editor_time;
            $value->save();
        }

        // Seed with old structure records
        /* if (config('app.env') === 'local') {
            Artisan::call('db:seed', [
                '--class' => 'OldDatabaseSeeder',
            ]);
        } */
        Schema::table('padaliniai', function (Blueprint $table) {
            $table->string('type')->nullable()->after('id')->default('padalinys');
            $table->unique('shortname');
        });

        DB::table('padaliniai')->insert(['id' => 16, 'fullname' => 'Vilniaus universiteto Studentų atstovybė', 'shortname' => 'VU SA', 'alias' => 'vusa', 'type' => 'pagrindinis', 'en' => 0]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
    }
}
