<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

class SeedWithOldStructureRecords extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        
        // Prerequisites
        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('created', 'created_at');
            $table->timestamp('email_verified_at')->nullable()->after('gid');
        });

        Schema::rename('saziningai', 'saziningai_exams');
        Schema::rename('saziningai_people', 'saziningai_observers');
        Schema::rename('sidebar', 'banners');
        Schema::rename('mainPage', 'main_page');
        Schema::rename('menu_new', 'menu');
        Schema::rename('page', 'pages');

        Schema::table('saziningai_exams', function (Blueprint $table) {
            $table->renameColumn('registration_time', 'created_at');
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        Schema::table('saziningai_observers', function (Blueprint $table) {
            $table->renameColumn('dateRegistered', 'created_at');
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        Schema::table('calendar', function (Blueprint $table) {
            $table->renameColumn('editor_time', 'updated_at');
        });

        Schema::table('calendar', function (Blueprint $table) {
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate()->after('created_at')->change();
        });


        Schema::table('banners', function (Blueprint $table) {
            $table->renameColumn('editor_time', 'updated_at');
        });

        Schema::table('banners', function (Blueprint $table) {
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate()->after('created_at')->change();
        });

        Schema::table('main_page', function (Blueprint $table) {
            $table->renameColumn('created_time', 'created_at');
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        Schema::table('pages', function (Blueprint $table) {
            $table->renameColumn('editor_time', 'updated_at');
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::table('news', function (Blueprint $table) {
            $table->renameColumn('editor_time', 'updated_at');
            $table->timestamp('created_at')->useCurrent();
        });

        // Seed with old structure records
        if (config('app.env') === 'local') {
            Artisan::call('db:seed', [
                '--class' => 'OldDatabaseSeeder',
            ]);
        }
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
