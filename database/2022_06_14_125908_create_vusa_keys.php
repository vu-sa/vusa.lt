<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateVusaKeys extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('banners', function (Blueprint $table) {
            // $table->foreign(['user_id'])->references(['id'])->on('users');
            $table->foreign(['padalinys_id'])->references(['id'])->on('padaliniai');
        });

        Schema::table('calendar', function (Blueprint $table) {
            $table->foreign(['padalinys_id'])->references(['id'])->on('padaliniai');
            $table->foreign(['category'])->references(['alias'])->on('categories');
            $table->foreign(['user_id'])->references(['id'])->on('users');
        });

        Schema::table('duties', function (Blueprint $table) {
            $table->foreign(['type_id'])->references(['id'])->on('duties_types');
            $table->foreign(['institution_id'])->references(['id'])->on('duties_institutions');
        });

        Schema::table('duties_institutions', function (Blueprint $table) {
            $table->foreign(['type_id'])->references(['id'])->on('duties_institutions_types');
            $table->foreign(['padalinys_id'])->references(['id'])->on('padaliniai');
        });

        Schema::table('duties_users', function (Blueprint $table) {
            $table->foreign(['user_id'])->references(['id'])->on('users');
            $table->foreign(['duty_id'])->references(['id'])->on('duties');
        });

        Schema::table('main_page', function (Blueprint $table) {
            $table->foreign(['user_id'])->references(['id'])->on('users');
            $table->foreign(['padalinys_id'])->references(['id'])->on('padaliniai');
        });

        Schema::table('navigation', function (Blueprint $table) {
            $table->foreign(['user_id'])->references(['id'])->on('users');
            $table->foreign(['padalinys_id'])->references(['id'])->on('padaliniai');
        });

        Schema::table('news', function (Blueprint $table) {
            $table->foreign(['padalinys_id'])->references(['id'])->on('padaliniai');
            $table->foreign(['category_id'])->references(['id'])->on('categories');
            $table->foreign(['user_id'])->references(['id'])->on('users');
        });

        Schema::table('pages', function (Blueprint $table) {
            $table->foreign(['user_id'])->references(['id'])->on('users');
            $table->foreign(['padalinys_id'])->references(['id'])->on('padaliniai');
        });

        Schema::table('posts_tags', function (Blueprint $table) {
            $table->foreign(['page_id'])->references(['id'])->on('pages');
            $table->foreign(['news_id'])->references(['id'])->on('news');
            $table->foreign(['tag_id'])->references(['id'])->on('tags');
        });

        Schema::table('role_user', function (Blueprint $table) {
            $table->foreign(['user_id'])->references(['id'])->on('users');
            $table->foreign(['role_id'])->references(['id'])->on('roles');
        });

        Schema::table('saziningai_exam_flows', function (Blueprint $table) {
            $table->foreign(['exam_uuid'])->references(['uuid'])->on('saziningai_exams')->onDelete('CASCADE');
        });

        Schema::table('saziningai_exams', function (Blueprint $table) {
            $table->foreign(['padalinys_id'])->references(['id'])->on('padaliniai');
        });

        Schema::table('saziningai_observers', function (Blueprint $table) {
            $table->foreign(['flow'])->references(['id'])->on('saziningai_exam_flows')->onDelete('CASCADE');
            $table->foreign(['exam_uuid'])->references(['uuid'])->on('saziningai_exams');
            $table->foreign(['padalinys_id'])->references(['id'])->on('padaliniai');
        });

        if (config('app.env') == 'local') {
            DB::unprepared(file_get_contents('database/vusa_www-2022.sql'));
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('saziningai_observers', function (Blueprint $table) {
            $table->dropForeign('saziningai_observers_flow_foreign');
            $table->dropForeign('saziningai_observers_exam_uuid_foreign');
            $table->dropForeign('saziningai_observers_padalinys_id_foreign');
        });

        Schema::table('saziningai_exams', function (Blueprint $table) {
            $table->dropForeign('saziningai_exams_padalinys_id_foreign');
        });

        Schema::table('saziningai_exam_flows', function (Blueprint $table) {
            $table->dropForeign('saziningai_exam_flows_exam_uuid_foreign');
        });

        Schema::table('role_user', function (Blueprint $table) {
            $table->dropForeign('role_user_user_id_foreign');
            $table->dropForeign('role_user_role_id_foreign');
        });

        Schema::table('posts_tags', function (Blueprint $table) {
            $table->dropForeign('posts_tags_page_id_foreign');
            $table->dropForeign('posts_tags_news_id_foreign');
            $table->dropForeign('posts_tags_tag_id_foreign');
        });

        Schema::table('pages', function (Blueprint $table) {
            $table->dropForeign('pages_user_id_foreign');
            $table->dropForeign('pages_padalinys_id_foreign');
        });

        Schema::table('news', function (Blueprint $table) {
            $table->dropForeign('news_padalinys_id_foreign');
            $table->dropForeign('news_category_id_foreign');
            $table->dropForeign('news_user_id_foreign');
        });

        Schema::table('navigation', function (Blueprint $table) {
            $table->dropForeign('navigation_user_id_foreign');
            $table->dropForeign('navigation_padalinys_id_foreign');
        });

        Schema::table('main_page', function (Blueprint $table) {
            $table->dropForeign('main_page_user_id_foreign');
            $table->dropForeign('main_page_padalinys_id_foreign');
        });

        Schema::table('duties_users', function (Blueprint $table) {
            $table->dropForeign('duties_users_user_id_foreign');
            $table->dropForeign('duties_users_duty_id_foreign');
        });

        Schema::table('duties_institutions', function (Blueprint $table) {
            $table->dropForeign('duties_institutions_type_id_foreign');
            $table->dropForeign('duties_institutions_padalinys_id_foreign');
        });

        Schema::table('duties', function (Blueprint $table) {
            $table->dropForeign('duties_type_id_foreign');
            $table->dropForeign('duties_institution_id_foreign');
        });

        Schema::table('calendar', function (Blueprint $table) {
            $table->dropForeign('calendar_padalinys_id_foreign');
            $table->dropForeign('calendar_category_foreign');
            $table->dropForeign('calendar_user_id_foreign');
        });

        Schema::table('banners', function (Blueprint $table) {
            $table->dropForeign('banners_user_id_foreign');
            $table->dropForeign('banners_padalinys_id_foreign');
        });
    }
}
