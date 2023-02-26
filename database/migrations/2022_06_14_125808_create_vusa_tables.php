<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateVusaTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('name');
            $table->string('password')->nullable();
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('role_id')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('remember_token', 60)->nullable();
            $table->timestamp('last_login')->nullable();
            $table->text('microsoft_token')->nullable();
            $table->string('google_token')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->unsignedBigInteger('team_id')->nullable();
            $table->string('profile_photo_path', 2048)->nullable();
        });

        Schema::create('banners', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title', 100);
            $table->text('image_url');
            $table->text('link_url');
            $table->string('lang', 2)->default('lt');
            $table->integer('order');
            $table->integer('is_active')->default(1);
            $table->unsignedInteger('user_id')->index('banners_user_id_foreign');
            $table->unsignedInteger('padalinys_id')->default(1)->index('banners_padalinys_id_foreign');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();

            $table->unique(['order', 'padalinys_id'], 'banners_order_role_id_unique');
        });

        Schema::create('calendar', function (Blueprint $table) {
            $table->increments('id');
            $table->dateTime('date');
            $table->text('title');
            $table->text('description')->nullable();
            $table->string('location')->nullable();
            $table->string('category')->nullable()->index('calendar_category_foreign');
            $table->mediumText('url')->nullable();
            $table->unsignedInteger('user_id')->nullable()->index('calendar_user_id_foreign');
            $table->unsignedInteger('padalinys_id')->default(16)->index('calendar_padalinys_id_foreign');
            $table->json('attributes')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
            $table->foreignId('registration_form_id')->nullable();
        });

        Schema::create('categories', function (Blueprint $table) {
            $table->increments('id');
            $table->string('alias')->nullable()->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
        });

        Schema::create('duties', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedInteger('type_id')->index('duties_type_id_foreign')->comment('Studentų atstovas, koordinatorius, etc.');
            $table->unsignedInteger('institution_id')->index('duties_institution_id_foreign');
            $table->string('email')->nullable()->comment('Commonly the @vusa.lt email address, which is used as the OAuth login. Personal mail is stored in users.email.');
            $table->json('attributes')->nullable()->comment('For specifying, e.g. study programme.');
            $table->unsignedInteger('places_to_occupy')->nullable()->default(1)->comment('Full number of positions to occupy for this duty');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
        });

        Schema::create('duties_institutions', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('pid')->nullable();
            $table->string('name')->nullable();
            $table->string('short_name')->nullable();
            $table->string('alias')->index();
            $table->text('description')->nullable();
            $table->string('image_url')->nullable();
            $table->unsignedInteger('type_id')->nullable()->index('duties_institutions_type_id_foreign');
            $table->unsignedInteger('padalinys_id')->nullable()->index('duties_institutions_padalinys_id_foreign')->comment('Should be changed to padaliniai_id.');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
            $table->json('attributes')->nullable();

            $table->unique(['pid', 'alias']);
        });

        Schema::create('duties_institutions_types', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('alias')->index();
            $table->text('description')->nullable();
            $table->json('attributes')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
        });

        Schema::create('duties_types', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('pid')->nullable();
            $table->string('name');
            $table->string('alias')->index();
            $table->text('description')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
        });

        Schema::create('duties_users', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('duty_id')->index('duties_users_duty_id_foreign');
            $table->unsignedInteger('user_id')->index('duties_users_user_id_foreign');
            $table->json('attributes')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
        });

        Schema::create('failed_jobs', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('uuid')->unique();
            $table->text('connection');
            $table->text('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();
        });

        Schema::create('main_page', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id')->nullable()->index('main_page_user_id_foreign');
            $table->string('link')->nullable();
            $table->string('text', 100)->nullable();
            $table->string('image', 100)->nullable();
            $table->string('position', 100);
            $table->integer('order')->nullable();
            $table->string('type', 60)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('padalinys_id')->index('main_page_padalinys_id_foreign');
            $table->string('lang', 2)->nullable()->default('lt');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
        });

        Schema::create('navigation', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('parent_id')->default(0);
            $table->unsignedInteger('user_id')->nullable()->index('navigation_user_id_foreign');
            $table->unsignedInteger('padalinys_id')->default(16)->index('navigation_padalinys_id_foreign');
            $table->string('name', 100);
            $table->string('lang', 2)->default('lt');
            $table->string('url');
            $table->integer('order');
            $table->boolean('is_active')->default(true);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
        });

        Schema::create('news', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id')->nullable()->index('news_user_id_foreign');
            $table->string('title', 200);
            $table->unsignedInteger('category_id')->nullable()->index('news_category_id_foreign');
            $table->string('permalink', 150)->nullable()->unique('permalink');
            $table->mediumText('short');
            $table->mediumText('text');
            $table->string('lang', 2)->default('lt');
            $table->unsignedInteger('other_lang_id')->nullable()->index();
            $table->string('image', 200)->nullable()->default('058543019bc51198ea1dc255580d215be99f2297.jpeg');
            $table->string('image_author', 200)->nullable()->default('VU SA archyvo nuotrauka');
            $table->boolean('important')->default(false);
            $table->unsignedInteger('padalinys_id')->index('news_padalinys_id_foreign');
            $table->timestamp('publish_time')->nullable();
            $table->mediumText('main_points')->nullable();
            $table->string('read_more', 100)->nullable();
            $table->boolean('draft')->nullable()->default(false);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();

            $table->unique(['other_lang_id']);
            $table->unique(['permalink', 'padalinys_id'], 'news_permalink_role_id_unique');
        });

        Schema::create('padaliniai', function (Blueprint $table) {
            $table->increments('id');
            $table->string('type')->nullable()->default('padalinys');
            $table->string('fullname', 100);
            $table->string('shortname', 100)->unique();
            $table->string('alias', 20);
            $table->boolean('en')->default(false);
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('address')->nullable();
            $table->string('shortname_vu');
        });

        Schema::create('page_views', function (Blueprint $table) {
            $table->increments('id');
            $table->string('host');
            $table->string('url');
            $table->string('session_id')->nullable();
            $table->unsignedInteger('user_id')->nullable();
            $table->string('ip');
            $table->text('agent')->nullable();
            $table->timestamps();
        });

        Schema::create('pages', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id')->nullable()->index('pages_user_id_foreign');
            $table->string('title', 200);
            $table->string('permalink', 200)->nullable();
            $table->mediumText('text');
            $table->string('lang', 2)->default('lt');
            $table->unsignedInteger('other_lang_id')->nullable()->unique();
            $table->unsignedInteger('category_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('padalinys_id')->index('pages_padalinys_id_foreign');
            $table->text('aside')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();

            $table->unique(['permalink', 'padalinys_id'], 'pages_permalink_role_id_unique');
            $table->index(['other_lang_id']);
        });

        Schema::create('password_resets', function (Blueprint $table) {
            $table->string('email')->index();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('posts_tags', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('page_id')->nullable();
            $table->unsignedInteger('tag_id')->index('posts_tags_tag_id_foreign');
            $table->unsignedInteger('news_id')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['news_id', 'tag_id']);
            $table->unique(['page_id', 'tag_id']);
        });

        Schema::create('role_user', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id')->index('role_user_user_id_foreign');
            $table->unsignedInteger('role_id')->index('role_user_role_id_foreign');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->increments('id');
            $table->text('description')->nullable();
            $table->string('alias')->nullable();
            $table->string('name');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
        });

        Schema::create('saziningai_exam_flows', function (Blueprint $table) {
            $table->increments('id');
            $table->string('exam_uuid', 30)->index('saziningai_exam_flows_exam_uuid_foreign');
            $table->dateTime('start_time');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
        });

        Schema::create('saziningai_exams', function (Blueprint $table) {
            $table->increments('id');
            $table->string('uuid', 30)->unique('uuid uniq');
            $table->string('name', 30)->nullable()->default('Anonimas');
            $table->string('email', 100)->nullable();
            $table->string('exam_type', 100)->nullable();
            $table->unsignedInteger('padalinys_id')->nullable()->index('saziningai_exams_padalinys_id_foreign');
            $table->string('place', 100)->nullable();
            $table->string('duration', 200)->nullable();
            $table->string('subject_name', 100)->nullable();
            $table->integer('exam_holders')->nullable();
            $table->unsignedInteger('students_need')->nullable();
            $table->string('phone')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
        });

        Schema::create('saziningai_observers', function (Blueprint $table) {
            $table->integer('id', true);
            $table->string('exam_uuid', 30)->index('saziningai_observers_exam_uuid_foreign');
            $table->string('name', 100);
            $table->string('email')->nullable();
            $table->string('phone', 100);
            $table->unsignedInteger('flow')->index('saziningai_observers_flow_foreign');
            $table->dateTime('created_at')->useCurrent();
            $table->string('has_arrived', 11);
            $table->string('phone_p')->nullable();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
            $table->unsignedInteger('padalinys_id')->default(16)->index('saziningai_observers_padalinys_id_foreign');
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->unique();
            $table->integer('user_id')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->text('payload');
            $table->integer('last_activity');
        });

        Schema::create('tags', function (Blueprint $table) {
            $table->increments('id');
            $table->string('alias')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
        });

        Schema::create('media', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->morphs('model');
            $table->uuid('uuid')->nullable()->unique();
            $table->string('collection_name');
            $table->string('name');
            $table->string('file_name');
            $table->string('mime_type')->nullable();
            $table->string('disk');
            $table->string('conversions_disk')->nullable();
            $table->unsignedBigInteger('size');
            $table->json('manipulations');
            $table->json('custom_properties');
            $table->json('generated_conversions');
            $table->json('responsive_images');
            $table->unsignedInteger('order_column')->nullable()->index();

            $table->nullableTimestamps();
        });

        Schema::create('registration_forms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable();
            $table->json('data');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        Schema::create('registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('registration_form_id');
            $table->json('data');
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
        Schema::dropIfExists('users');

        Schema::dropIfExists('tags');

        Schema::dropIfExists('sessions');

        Schema::dropIfExists('saziningai_observers');

        Schema::dropIfExists('saziningai_exams');

        Schema::dropIfExists('saziningai_exam_flows');

        Schema::dropIfExists('roles');

        Schema::dropIfExists('role_user');

        Schema::dropIfExists('posts_tags');

        Schema::dropIfExists('password_resets');

        Schema::dropIfExists('pages');

        Schema::dropIfExists('page_views');

        Schema::dropIfExists('padaliniai');

        Schema::dropIfExists('news');

        Schema::dropIfExists('navigation');

        Schema::dropIfExists('main_page');

        Schema::dropIfExists('failed_jobs');

        Schema::dropIfExists('duties_users');

        Schema::dropIfExists('duties_types');

        Schema::dropIfExists('duties_institutions_types');

        Schema::dropIfExists('duties_institutions');

        Schema::dropIfExists('duties');

        Schema::dropIfExists('categories');

        Schema::dropIfExists('calendar');

        Schema::dropIfExists('banners');

        Schema::dropIfExists('media');

        Schema::dropIfExists('registration_forms');

        Schema::dropIfExists('registrations');
    }
}
