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
        if (!Schema::hasTable('activity_log')) {
            Schema::create('activity_log', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('log_name')->nullable()->index();
                $table->text('description');
                $table->string('subject_type')->nullable();
                $table->string('event')->nullable();
                $table->string('subject_id', 26)->nullable();
                $table->string('causer_type')->nullable();
                $table->string('causer_id', 26)->nullable();
                $table->json('properties')->nullable();
                $table->char('batch_uuid', 36)->nullable();
                $table->timestamps();

                $table->index(['causer_type', 'causer_id'], 'causer');
                $table->index(['subject_type', 'subject_id'], 'subject');
            });
        }

        if (!Schema::hasTable('agenda_items')) {
            Schema::create('agenda_items', function (Blueprint $table) {
                $table->char('id', 26)->primary();
                $table->char('meeting_id', 26)->index('agenda_items_meeting_id_foreign');
                $table->char('matter_id', 26)->nullable()->index('agenda_items_matter_id_foreign');
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
                $table->text('title');
                $table->time('start_time')->nullable();
                $table->string('outcome', 255)->nullable();
            });
        }

        if (!Schema::hasTable('banners')) {
            Schema::create('banners', function (Blueprint $table) {
                $table->increments('id');
                $table->string('title', 100);
                $table->text('image_url');
                $table->text('link_url');
                $table->string('lang', 2)->default('lt');
                $table->integer('order');
                $table->integer('is_active')->default(1);
                $table->unsignedInteger('padalinys_id')->default(1)->index('banners_padalinys_id_foreign');
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();

                $table->unique(['order', 'padalinys_id'], 'banners_order_role_id_unique');
            });
        }

        if (!Schema::hasTable('calendar')) {
            Schema::create('calendar', function (Blueprint $table) {
                $table->increments('id');
                $table->dateTime('date');
                $table->dateTime('end_date')->nullable();
                $table->text('title');
                $table->text('description')->nullable();
                $table->string('location', 255)->nullable();
                $table->string('category', 255)->nullable()->index('calendar_category_foreign');
                $table->mediumText('url')->nullable();
                $table->unsignedInteger('padalinys_id')->default(16)->index('calendar_padalinys_id_foreign');
                $table->longText('extra_attributes')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
                $table->unsignedBigInteger('registration_form_id')->nullable();
            });
        }

        if (!Schema::hasTable('categories')) {
            Schema::create('categories', function (Blueprint $table) {
                $table->increments('id');
                $table->string('alias', 255)->nullable()->unique();
                $table->string('name', 255);
                $table->text('description')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
            });
        }

        if (!Schema::hasTable('changelog_items')) {
            Schema::create('changelog_items', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->json('title');
                $table->dateTime('date');
                $table->json('description');
            });
        }

        if (!Schema::hasTable('comments')) {
            Schema::create('comments', function (Blueprint $table) {
                $table->char('id', 26)->primary();
                $table->char('parent_id', 26)->nullable();
                $table->text('comment');
                $table->string('decision')->nullable()->comment('The decision made alongside the comment.');
                $table->char('user_id', 26)->index('comments_user_id_foreign');
                $table->string('commentable_type');
                $table->char('commentable_id', 36);
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
                $table->softDeletes();

                $table->index(['commentable_type', 'commentable_id']);
            });
        }

        if (!Schema::hasTable('contacts')) {
            Schema::create('contacts', function (Blueprint $table) {
                $table->char('id', 26)->primary();
                $table->string('name');
                $table->string('email')->nullable();
                $table->string('phone')->nullable();
                $table->string('profile_photo_path')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
                $table->softDeletes();
                $table->json('extra_attributes')->nullable();
            });
        }

        if (!Schema::hasTable('doables')) {
            Schema::create('doables', function (Blueprint $table) {
                $table->string('doable_type');
                $table->char('doable_id', 26);
                $table->char('doing_id', 26)->index('doables_doing_id_foreign');
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();

                $table->primary(['doable_id', 'doable_type', 'doing_id']);
            });
        }

        if (!Schema::hasTable('doing_user')) {
            Schema::create('doing_user', function (Blueprint $table) {
                $table->char('doing_id', 26);
                $table->char('user_id', 26)->index('doing_user_user_id_foreign');
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();

                $table->primary(['doing_id', 'user_id']);
            });
        }

        if (!Schema::hasTable('doings')) {
            Schema::create('doings', function (Blueprint $table) {
                $table->char('id', 26)->primary();
                $table->string('title');
                $table->string('drive_item_name')->nullable()->unique()->comment('The name of the folder in the Sharepoint drive');
                $table->string('state')->index('doings_status_index');
                $table->dateTime('date');
                $table->json('extra_attributes')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('dutiables')) {
            Schema::create('dutiables', function (Blueprint $table) {
                $table->char('duty_id', 26)->index('duties_users_duty_id_foreign');
                $table->char('dutiable_id', 26)->index('duties_users_user_id_foreign');
                $table->string('dutiable_type');
                $table->date('start_date');
                $table->date('end_date')->nullable();
                $table->longText('extra_attributes')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();

                $table->primary(['dutiable_id', 'dutiable_type', 'duty_id', 'start_date']);
            });
        }

        if (!Schema::hasTable('duties')) {
            Schema::create('duties', function (Blueprint $table) {
                $table->char('id', 26)->primary();
                $table->string('name', 255);
                $table->text('description')->nullable();
                $table->char('institution_id', 26)->index('duties_institution_id_foreign');
                $table->unsignedInteger('order')->default(0)->comment('Order of duty in institution');
                $table->string('email', 255)->nullable()->comment('Commonly the @vusa.lt email address, which is used as the OAuth login. Personal mail is stored in users.email.');
                $table->longText('extra_attributes')->nullable()->comment('For specifying, e.g. study programme.');
                $table->unsignedInteger('places_to_occupy')->nullable()->default(1)->comment('Full number of positions to occupy for this duty');
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
                $table->softDeletes();

                $table->unique(['name', 'institution_id', 'email']);
            });
        }

        if (!Schema::hasTable('failed_jobs')) {
            Schema::create('failed_jobs', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('uuid', 255)->unique();
                $table->text('connection');
                $table->text('queue');
                $table->longText('payload');
                $table->longText('exception');
                $table->timestamp('failed_at')->useCurrent();
            });
        }

        if (!Schema::hasTable('goal_groups')) {
            Schema::create('goal_groups', function (Blueprint $table) {
                $table->char('id', 26)->primary();
                $table->string('title');
                $table->text('description')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('goal_matter')) {
            Schema::create('goal_matter', function (Blueprint $table) {
                $table->char('goal_id', 26);
                $table->char('matter_id', 26)->index('goal_matter_matter_id_foreign');
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();

                $table->primary(['goal_id', 'matter_id']);
            });
        }

        if (!Schema::hasTable('goals')) {
            Schema::create('goals', function (Blueprint $table) {
                $table->char('id', 26)->primary();
                $table->char('group_id', 26)->nullable()->index('goals_group_id_foreign');
                $table->unsignedInteger('padalinys_id')->index('goals_padalinys_id_foreign');
                $table->string('title');
                $table->text('description')->nullable();
                $table->date('start_date');
                $table->date('end_date')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
                $table->softDeletes();

                $table->unique(['title', 'padalinys_id']);
            });
        }

        if (!Schema::hasTable('institution_meeting')) {
            Schema::create('institution_meeting', function (Blueprint $table) {
                $table->char('institution_id', 26);
                $table->char('meeting_id', 26)->index('institution_meeting_meeting_id_foreign');
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();

                $table->primary(['institution_id', 'meeting_id']);
            });
        }

        if (!Schema::hasTable('institutions')) {
            Schema::create('institutions', function (Blueprint $table) {
                $table->char('id', 26)->primary();
                $table->char('parent_id', 26)->nullable();
                $table->string('name', 255)->nullable();
                $table->string('short_name', 255)->nullable();
                $table->string('alias', 255);
                $table->text('description')->nullable();
                $table->string('image_url', 255)->nullable();
                $table->unsignedInteger('padalinys_id')->nullable()->index('duties_institutions_padalinys_id_foreign');
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
                $table->longText('extra_attributes')->nullable();
                $table->softDeletes();

                $table->unique(['name', 'padalinys_id']);
                $table->unique(['parent_id', 'alias']);
            });
        }

        if (!Schema::hasTable('institutions_matters')) {
            Schema::create('institutions_matters', function (Blueprint $table) {
                $table->char('institution_id', 26);
                $table->char('matter_id', 26)->index('institutions_matters_matter_id_foreign');
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();

                $table->primary(['institution_id', 'matter_id']);
            });
        }

        if (!Schema::hasTable('jobs')) {
            Schema::create('jobs', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('queue')->index();
                $table->longText('payload');
                $table->unsignedTinyInteger('attempts');
                $table->unsignedInteger('reserved_at')->nullable();
                $table->unsignedInteger('available_at');
                $table->unsignedInteger('created_at');
            });
        }

        if (!Schema::hasTable('main_page')) {
            Schema::create('main_page', function (Blueprint $table) {
                $table->increments('id');
                $table->string('link', 255)->nullable();
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
        }

        if (!Schema::hasTable('matters')) {
            Schema::create('matters', function (Blueprint $table) {
                $table->char('id', 26)->primary();
                $table->string('title');
                $table->text('description')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('media')) {
            Schema::create('media', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('model_type', 255);
                $table->unsignedInteger('model_id');
                $table->char('uuid', 36)->nullable()->unique();
                $table->string('collection_name', 255);
                $table->string('name', 255);
                $table->string('file_name', 255);
                $table->string('mime_type', 255)->nullable();
                $table->string('disk', 255);
                $table->string('conversions_disk', 255)->nullable();
                $table->unsignedBigInteger('size');
                $table->json('manipulations');
                $table->json('custom_properties');
                $table->json('generated_conversions');
                $table->json('responsive_images');
                $table->unsignedInteger('order_column')->nullable()->index();
                $table->timestamps();

                $table->index(['model_type', 'model_id']);
            });
        }

        if (!Schema::hasTable('meetings')) {
            Schema::create('meetings', function (Blueprint $table) {
                $table->char('id', 26)->primary();
                $table->string('title');
                $table->text('description')->nullable();
                $table->dateTime('start_time');
                $table->dateTime('end_time')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('model_has_permissions')) {
            Schema::create('model_has_permissions', function (Blueprint $table) {
                $table->char('permission_id', 26);
                $table->string('model_type');
                $table->char('model_id', 26);

                $table->index(['model_id', 'model_type']);
                $table->primary(['permission_id', 'model_id', 'model_type']);
            });
        }

        if (!Schema::hasTable('model_has_roles')) {
            Schema::create('model_has_roles', function (Blueprint $table) {
                $table->char('role_id', 26);
                $table->string('model_type');
                $table->char('model_id', 26);

                $table->index(['model_id', 'model_type']);
                $table->primary(['role_id', 'model_id', 'model_type']);
            });
        }

        if (!Schema::hasTable('navigation')) {
            Schema::create('navigation', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('parent_id')->default(0);
                $table->unsignedInteger('padalinys_id')->default(16)->index('navigation_padalinys_id_foreign');
                $table->string('name', 100);
                $table->string('lang', 2)->default('lt');
                $table->string('url', 255);
                $table->integer('order');
                $table->boolean('is_active')->default(true);
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
            });
        }

        if (!Schema::hasTable('news')) {
            Schema::create('news', function (Blueprint $table) {
                $table->increments('id');
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

                $table->unique(['permalink', 'padalinys_id'], 'news_permalink_role_id_unique');
                $table->unique(['other_lang_id']);
            });
        }

        if (!Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table) {
                $table->char('id', 36)->primary();
                $table->string('type');
                $table->string('notifiable_type');
                $table->char('notifiable_id', 26);
                $table->text('data');
                $table->timestamp('read_at')->nullable();
                $table->timestamps();

                $table->index(['notifiable_type', 'notifiable_id']);
            });
        }

        if (!Schema::hasTable('padaliniai')) {
            Schema::create('padaliniai', function (Blueprint $table) {
                $table->increments('id');
                $table->string('type', 255)->nullable()->default('padalinys');
                $table->string('fullname', 100);
                $table->string('shortname', 100)->unique();
                $table->string('alias', 20);
                $table->boolean('en')->default(false);
                $table->string('phone', 255)->nullable();
                $table->string('email', 255)->nullable();
                $table->string('address', 255)->nullable();
                $table->string('shortname_vu', 255);
            });
        }

        if (!Schema::hasTable('pages')) {
            Schema::create('pages', function (Blueprint $table) {
                $table->increments('id');
                $table->string('title', 200);
                $table->string('permalink', 200)->nullable();
                $table->mediumText('text');
                $table->string('lang', 2)->default('lt');
                $table->unsignedInteger('other_lang_id')->nullable()->unique();
                $table->unsignedInteger('category_id')->nullable();
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('padalinys_id')->index('pages_padalinys_id_foreign');
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();

                $table->index(['other_lang_id']);
                $table->unique(['permalink', 'padalinys_id'], 'pages_permalink_role_id_unique');
            });
        }

        if (!Schema::hasTable('permissions')) {
            Schema::create('permissions', function (Blueprint $table) {
                $table->char('id', 26)->primary();
                $table->string('name');
                $table->string('guard_name');
                $table->timestamps();

                $table->unique(['name', 'guard_name']);
            });
        }

        if (!Schema::hasTable('posts_tags')) {
            Schema::create('posts_tags', function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('page_id')->nullable();
                $table->unsignedInteger('tag_id')->index('posts_tags_tag_id_foreign');
                $table->unsignedInteger('news_id')->nullable();
                $table->timestamp('created_at')->useCurrent();

                $table->unique(['news_id', 'tag_id']);
                $table->unique(['page_id', 'tag_id']);
            });
        }

        if (!Schema::hasTable('registration_forms')) {
            Schema::create('registration_forms', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('user_id')->nullable();
                $table->json('data');
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
            });
        }

        if (!Schema::hasTable('registrations')) {
            Schema::create('registrations', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('registration_form_id');
                $table->json('data');
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
            });
        }

        if (!Schema::hasTable('relationshipables')) {
            Schema::create('relationshipables', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('relationship_id')->index('relationshipables_relationship_id_foreign');
                $table->string('relationshipable_type');
                $table->string('relationshipable_id', 26);
                $table->string('related_model_id', 26)->index('related_model_id_index');
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();

                $table->index(['relationshipable_type', 'relationshipable_id'], 'relationshipable_id_index');
            });
        }

        if (!Schema::hasTable('relationships')) {
            Schema::create('relationships', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name');
                $table->string('slug');
                $table->text('description')->nullable();
                $table->string('type')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
            });
        }

        if (!Schema::hasTable('reservation_resource')) {
            Schema::create('reservation_resource', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->char('reservation_id', 26)->index('reservation_resource_reservation_id_foreign');
                $table->char('resource_id', 26)->index('reservation_resource_resource_id_foreign');
                $table->dateTime('start_time')->nullable();
                $table->dateTime('end_time')->nullable();
                $table->unsignedInteger('quantity')->default(1);
                $table->string('state')->default('created');
                $table->dateTime('returned_at')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('reservation_user')) {
            Schema::create('reservation_user', function (Blueprint $table) {
                $table->char('reservation_id', 26);
                $table->char('user_id', 26)->index('reservation_user_user_id_foreign');
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();

                $table->primary(['reservation_id', 'user_id']);
            });
        }

        if (!Schema::hasTable('reservations')) {
            Schema::create('reservations', function (Blueprint $table) {
                $table->char('id', 26)->primary();
                $table->json('name');
                $table->json('description')->nullable();
                $table->dateTime('start_time');
                $table->dateTime('end_time');
                $table->dateTime('completed_at')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('resources')) {
            Schema::create('resources', function (Blueprint $table) {
                $table->char('id', 26)->primary();
                $table->json('name');
                $table->json('description')->nullable();
                $table->string('location')->nullable();
                $table->unsignedInteger('capacity')->default(1);
                $table->unsignedInteger('padalinys_id')->index('resources_padalinys_id_foreign');
                $table->boolean('is_reservable')->default(true);
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('role_has_permissions')) {
            Schema::create('role_has_permissions', function (Blueprint $table) {
                $table->char('permission_id', 26)->index('role_has_permissions_permission_id_foreign');
                $table->char('role_id', 26)->index('role_has_permissions_role_id_foreign');

                $table->primary(['permission_id', 'role_id']);
            });
        }

        if (!Schema::hasTable('roles')) {
            Schema::create('roles', function (Blueprint $table) {
                $table->char('id', 26)->primary();
                $table->string('name');
                $table->string('guard_name');
                $table->timestamps();

                $table->unique(['name', 'guard_name']);
            });
        }

        if (!Schema::hasTable('saziningai_exam_flows')) {
            Schema::create('saziningai_exam_flows', function (Blueprint $table) {
                $table->increments('id');
                $table->string('exam_uuid', 30)->index('saziningai_exam_flows_exam_uuid_foreign');
                $table->dateTime('start_time');
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
            });
        }

        if (!Schema::hasTable('saziningai_exams')) {
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
                $table->string('phone', 255)->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
            });
        }

        if (!Schema::hasTable('saziningai_observers')) {
            Schema::create('saziningai_observers', function (Blueprint $table) {
                $table->integer('id', true);
                $table->string('exam_uuid', 30)->index('saziningai_observers_exam_uuid_foreign');
                $table->string('name', 100);
                $table->string('email', 255)->nullable();
                $table->string('phone', 100);
                $table->unsignedInteger('flow')->index('saziningai_observers_flow_foreign');
                $table->dateTime('created_at')->useCurrent();
                $table->string('has_arrived', 11);
                $table->string('phone_p', 255)->nullable();
                $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
                $table->unsignedInteger('padalinys_id')->default(16)->index('saziningai_observers_padalinys_id_foreign');
            });
        }

        if (!Schema::hasTable('sessions')) {
            Schema::create('sessions', function (Blueprint $table) {
                $table->string('id', 255)->unique();
                $table->char('user_id', 26)->nullable();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->text('payload');
                $table->integer('last_activity');
            });
        }

        if (!Schema::hasTable('sharepoint_fileables')) {
            Schema::create('sharepoint_fileables', function (Blueprint $table) {
                $table->char('sharepoint_file_id', 36);
                $table->string('fileable_type');
                $table->char('fileable_id', 26);
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();

                $table->index(['fileable_type', 'fileable_id']);
                $table->unique(['sharepoint_file_id', 'fileable_id', 'fileable_type'], 'sharepoint_fileables_unique');
            });
        }

        if (!Schema::hasTable('sharepoint_files')) {
            Schema::create('sharepoint_files', function (Blueprint $table) {
                $table->string('sharepoint_id');
                $table->char('id', 36)->primary();
            });
        }

        if (!Schema::hasTable('tags')) {
            Schema::create('tags', function (Blueprint $table) {
                $table->increments('id');
                $table->string('alias', 255)->nullable();
                $table->string('name', 255);
                $table->text('description')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
            });
        }

        if (!Schema::hasTable('task_user')) {
            Schema::create('task_user', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->char('task_id', 26)->index('task_user_task_id_foreign');
                $table->char('user_id', 26)->index('task_user_user_id_foreign');
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
            });
        }

        if (!Schema::hasTable('tasks')) {
            Schema::create('tasks', function (Blueprint $table) {
                $table->char('id', 26)->primary();
                $table->string('name');
                $table->text('description')->nullable();
                $table->date('due_date')->nullable();
                $table->string('taskable_type');
                $table->char('taskable_id', 26);
                $table->timestamp('completed_at')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
                $table->softDeletes();

                $table->index(['taskable_type', 'taskable_id']);
            });
        }

        if (!Schema::hasTable('typeables')) {
            Schema::create('typeables', function (Blueprint $table) {
                $table->unsignedBigInteger('type_id');
                $table->string('typeable_type');
                $table->char('typeable_id', 26);

                $table->index(['typeable_type', 'typeable_id']);
                $table->unique(['type_id', 'typeable_id', 'typeable_type']);
            });
        }

        if (!Schema::hasTable('types')) {
            Schema::create('types', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('parent_id')->nullable()->index('types_parent_id_foreign');
                $table->string('title')->nullable();
                $table->string('model_type')->nullable();
                $table->text('description')->nullable();
                $table->string('slug')->nullable();
                $table->json('extra_attributes')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->useCurrentOnUpdate()->useCurrent();
                $table->softDeletes();

                $table->unique(['title', 'model_type']);
            });
        }

        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->char('id', 26)->primary();
                $table->string('email', 255)->unique();
                $table->string('phone', 255)->nullable();
                $table->string('name', 255);
                $table->string('password', 255)->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamp('email_verified_at')->nullable();
                $table->string('remember_token', 60)->nullable();
                $table->timestamp('last_action')->nullable();
                $table->dateTime('last_changelog_check')->nullable();
                $table->text('microsoft_token')->nullable();
                $table->string('google_token', 255)->nullable();
                $table->timestamp('updated_at')->nullable();
                $table->timestamp('created_at')->useCurrent();
                $table->string('profile_photo_path', 2048)->nullable();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('websockets_statistics_entries')) {
            Schema::create('websockets_statistics_entries', function (Blueprint $table) {
                $table->increments('id');
                $table->string('app_id');
                $table->integer('peak_connection_count');
                $table->integer('websocket_message_count');
                $table->integer('api_message_count');
                $table->timestamps();
            });
        }

        if (Schema::hasTable('agenda_items')) {
            Schema::table('agenda_items', function (Blueprint $table) {
                $table->foreign(['matter_id'], 'institution_meeting_matter_matter_id_foreign')->references(['id'])->on('matters');
                $table->foreign(['meeting_id'], 'institution_meeting_matter_meeting_id_foreign')->references(['id'])->on('meetings');
            });
        }

        if (Schema::hasTable('banners')) {
            Schema::table('banners', function (Blueprint $table) {
                $table->foreign(['padalinys_id'])->references(['id'])->on('padaliniai');
            });
        }

        if (Schema::hasTable('calendar')) {
            Schema::table('calendar', function (Blueprint $table) {
                $table->foreign(['category'])->references(['alias'])->on('categories');
                $table->foreign(['padalinys_id'])->references(['id'])->on('padaliniai');
            });
        }

        if (Schema::hasTable('comments')) {
            Schema::table('comments', function (Blueprint $table) {
                $table->foreign(['user_id'])->references(['id'])->on('users');
            });
        }

        if (Schema::hasTable('doables')) {
            Schema::table('doables', function (Blueprint $table) {
                $table->foreign(['doing_id'])->references(['id'])->on('doings');
            });
        }

        if (Schema::hasTable('doing_user')) {
            Schema::table('doing_user', function (Blueprint $table) {
                $table->foreign(['doing_id'])->references(['id'])->on('doings')->onDelete('CASCADE');
                $table->foreign(['user_id'])->references(['id'])->on('users')->onDelete('CASCADE');
            });
        }

        if (Schema::hasTable('dutiables')) {
            Schema::table('dutiables', function (Blueprint $table) {
                $table->foreign(['duty_id'])->references(['id'])->on('duties');
            });
        }

        if (Schema::hasTable('goal_matter')) {
            Schema::table('goal_matter', function (Blueprint $table) {
                $table->foreign(['goal_id'], 'goal_institution_matter_goal_id_foreign')->references(['id'])->on('goals');
                $table->foreign(['goal_id'])->references(['id'])->on('goals')->onDelete('CASCADE');
                $table->foreign(['matter_id'], 'goal_institution_matter_matter_id_foreign')->references(['id'])->on('matters');
                $table->foreign(['matter_id'])->references(['id'])->on('matters')->onDelete('CASCADE');
            });
        }

        if (Schema::hasTable('goals')) {
            Schema::table('goals', function (Blueprint $table) {
                $table->foreign(['group_id'])->references(['id'])->on('goal_groups');
                $table->foreign(['padalinys_id'])->references(['id'])->on('padaliniai');
            });
        }

        if (Schema::hasTable('institution_meeting')) {
            Schema::table('institution_meeting', function (Blueprint $table) {
                $table->foreign(['institution_id'])->references(['id'])->on('institutions');
                $table->foreign(['meeting_id'])->references(['id'])->on('meetings');
            });
        }

        if (Schema::hasTable('institutions')) {
            Schema::table('institutions', function (Blueprint $table) {
                $table->foreign(['padalinys_id'])->references(['id'])->on('padaliniai');
            });
        }

        if (Schema::hasTable('institutions_matters')) {
            Schema::table('institutions_matters', function (Blueprint $table) {
                $table->foreign(['institution_id'])->references(['id'])->on('institutions')->onDelete('CASCADE');
                $table->foreign(['matter_id'])->references(['id'])->on('matters')->onDelete('CASCADE');
            });
        }

        if (Schema::hasTable('main_page')) {
            Schema::table('main_page', function (Blueprint $table) {
                $table->foreign(['padalinys_id'])->references(['id'])->on('padaliniai');
            });
        }

        if (Schema::hasTable('model_has_permissions')) {
            Schema::table('model_has_permissions', function (Blueprint $table) {
                $table->foreign(['permission_id'])->references(['id'])->on('permissions')->onDelete('CASCADE');
            });
        }

        if (Schema::hasTable('model_has_roles')) {
            Schema::table('model_has_roles', function (Blueprint $table) {
                $table->foreign(['role_id'])->references(['id'])->on('roles')->onDelete('CASCADE');
            });
        }

        if (Schema::hasTable('navigation')) {
            Schema::table('navigation', function (Blueprint $table) {
                $table->foreign(['padalinys_id'])->references(['id'])->on('padaliniai');
            });
        }

        if (Schema::hasTable('news')) {
            Schema::table('news', function (Blueprint $table) {
                $table->foreign(['category_id'])->references(['id'])->on('categories');
                $table->foreign(['padalinys_id'])->references(['id'])->on('padaliniai');
            });
        }

        if (Schema::hasTable('pages')) {
            Schema::table('pages', function (Blueprint $table) {
                $table->foreign(['padalinys_id'])->references(['id'])->on('padaliniai');
            });
        }

        if (Schema::hasTable('posts_tags')) {
            Schema::table('posts_tags', function (Blueprint $table) {
                $table->foreign(['news_id'])->references(['id'])->on('news');
                $table->foreign(['tag_id'])->references(['id'])->on('tags');
                $table->foreign(['page_id'])->references(['id'])->on('pages');
            });
        }

        if (Schema::hasTable('relationshipables')) {
            Schema::table('relationshipables', function (Blueprint $table) {
                $table->foreign(['relationship_id'])->references(['id'])->on('relationships');
            });
        }

        if (Schema::hasTable('reservation_resource')) {
            Schema::table('reservation_resource', function (Blueprint $table) {
                $table->foreign(['reservation_id'])->references(['id'])->on('reservations');
                $table->foreign(['resource_id'])->references(['id'])->on('resources');
            });
        }

        if (Schema::hasTable('reservation_user')) {
            Schema::table('reservation_user', function (Blueprint $table) {
                $table->foreign(['reservation_id'])->references(['id'])->on('reservations')->onDelete('CASCADE');
                $table->foreign(['user_id'])->references(['id'])->on('users')->onDelete('CASCADE');
            });
        }

        if (Schema::hasTable('resources')) {
            Schema::table('resources', function (Blueprint $table) {
                $table->foreign(['padalinys_id'])->references(['id'])->on('padaliniai');
            });
        }

        if (Schema::hasTable('role_has_permissions')) {
            Schema::table('role_has_permissions', function (Blueprint $table) {
                $table->foreign(['permission_id'])->references(['id'])->on('permissions')->onDelete('CASCADE');
                $table->foreign(['role_id'])->references(['id'])->on('roles')->onDelete('CASCADE');
            });
        }

        if (Schema::hasTable('saziningai_exam_flows')) {
            Schema::table('saziningai_exam_flows', function (Blueprint $table) {
                $table->foreign(['exam_uuid'])->references(['uuid'])->on('saziningai_exams')->onDelete('CASCADE');
            });
        }

        if (Schema::hasTable('saziningai_exams')) {
            Schema::table('saziningai_exams', function (Blueprint $table) {
                $table->foreign(['padalinys_id'])->references(['id'])->on('padaliniai');
            });
        }

        if (Schema::hasTable('saziningai_observers')) {
            Schema::table('saziningai_observers', function (Blueprint $table) {
                $table->foreign(['exam_uuid'])->references(['uuid'])->on('saziningai_exams');
                $table->foreign(['padalinys_id'])->references(['id'])->on('padaliniai');
                $table->foreign(['flow'])->references(['id'])->on('saziningai_exam_flows')->onDelete('CASCADE');
            });
        }

        if (Schema::hasTable('sharepoint_fileables')) {
            Schema::table('sharepoint_fileables', function (Blueprint $table) {
                $table->foreign(['sharepoint_file_id'])->references(['id'])->on('sharepoint_files')->onDelete('CASCADE');
            });
        }

        if (Schema::hasTable('task_user')) {
            Schema::table('task_user', function (Blueprint $table) {
                $table->foreign(['task_id'])->references(['id'])->on('tasks');
            });
        }

        if (Schema::hasTable('typeables')) {
            Schema::table('typeables', function (Blueprint $table) {
                $table->foreign(['type_id'])->references(['id'])->on('types')->onDelete('CASCADE');
            });
        }

        if (Schema::hasTable('types')) {
            Schema::table('types', function (Blueprint $table) {
                $table->foreign(['parent_id'])->references(['id'])->on('types')->onDelete('SET NULL');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('types')) {
            Schema::table('types', function (Blueprint $table) {
                $table->dropForeign('types_parent_id_foreign');
            });
        }

        if (Schema::hasTable('typeables')) {
            Schema::table('typeables', function (Blueprint $table) {
                $table->dropForeign('typeables_type_id_foreign');
            });
        }

        if (Schema::hasTable('task_user')) {
            Schema::table('task_user', function (Blueprint $table) {
                $table->dropForeign('task_user_task_id_foreign');
            });
        }

        if (Schema::hasTable('sharepoint_fileables')) {
            Schema::table('sharepoint_fileables', function (Blueprint $table) {
                $table->dropForeign('sharepoint_fileables_sharepoint_file_id_foreign');
            });
        }

        if (Schema::hasTable('saziningai_observers')) {
            Schema::table('saziningai_observers', function (Blueprint $table) {
                $table->dropForeign('saziningai_observers_exam_uuid_foreign');
                $table->dropForeign('saziningai_observers_padalinys_id_foreign');
                $table->dropForeign('saziningai_observers_flow_foreign');
            });
        }

        if (Schema::hasTable('saziningai_exams')) {
            Schema::table('saziningai_exams', function (Blueprint $table) {
                $table->dropForeign('saziningai_exams_padalinys_id_foreign');
            });
        }

        if (Schema::hasTable('saziningai_exam_flows')) {
            Schema::table('saziningai_exam_flows', function (Blueprint $table) {
                $table->dropForeign('saziningai_exam_flows_exam_uuid_foreign');
            });
        }

        if (Schema::hasTable('role_has_permissions')) {
            Schema::table('role_has_permissions', function (Blueprint $table) {
                $table->dropForeign('role_has_permissions_permission_id_foreign');
                $table->dropForeign('role_has_permissions_role_id_foreign');
            });
        }

        if (Schema::hasTable('resources')) {
            Schema::table('resources', function (Blueprint $table) {
                $table->dropForeign('resources_padalinys_id_foreign');
            });
        }

        if (Schema::hasTable('reservation_user')) {
            Schema::table('reservation_user', function (Blueprint $table) {
                $table->dropForeign('reservation_user_reservation_id_foreign');
                $table->dropForeign('reservation_user_user_id_foreign');
            });
        }

        if (Schema::hasTable('reservation_resource')) {
            Schema::table('reservation_resource', function (Blueprint $table) {
                $table->dropForeign('reservation_resource_reservation_id_foreign');
                $table->dropForeign('reservation_resource_resource_id_foreign');
            });
        }

        if (Schema::hasTable('relationshipables')) {
            Schema::table('relationshipables', function (Blueprint $table) {
                $table->dropForeign('relationshipables_relationship_id_foreign');
            });
        }

        if (Schema::hasTable('posts_tags')) {
            Schema::table('posts_tags', function (Blueprint $table) {
                $table->dropForeign('posts_tags_news_id_foreign');
                $table->dropForeign('posts_tags_tag_id_foreign');
                $table->dropForeign('posts_tags_page_id_foreign');
            });
        }

        if (Schema::hasTable('pages')) {
            Schema::table('pages', function (Blueprint $table) {
                $table->dropForeign('pages_padalinys_id_foreign');
            });
        }

        if (Schema::hasTable('news')) {
            Schema::table('news', function (Blueprint $table) {
                $table->dropForeign('news_category_id_foreign');
                $table->dropForeign('news_padalinys_id_foreign');
            });
        }

        if (Schema::hasTable('navigation')) {
            Schema::table('navigation', function (Blueprint $table) {
                $table->dropForeign('navigation_padalinys_id_foreign');
            });
        }

        if (Schema::hasTable('model_has_roles')) {
            Schema::table('model_has_roles', function (Blueprint $table) {
                $table->dropForeign('model_has_roles_role_id_foreign');
            });
        }

        if (Schema::hasTable('model_has_permissions')) {
            Schema::table('model_has_permissions', function (Blueprint $table) {
                $table->dropForeign('model_has_permissions_permission_id_foreign');
            });
        }

        if (Schema::hasTable('main_page')) {
            Schema::table('main_page', function (Blueprint $table) {
                $table->dropForeign('main_page_padalinys_id_foreign');
            });
        }

        if (Schema::hasTable('institutions_matters')) {
            Schema::table('institutions_matters', function (Blueprint $table) {
                $table->dropForeign('institutions_matters_institution_id_foreign');
                $table->dropForeign('institutions_matters_matter_id_foreign');
            });
        }

        if (Schema::hasTable('institutions')) {
            Schema::table('institutions', function (Blueprint $table) {
                $table->dropForeign('institutions_padalinys_id_foreign');
            });
        }

        if (Schema::hasTable('institution_meeting')) {
            Schema::table('institution_meeting', function (Blueprint $table) {
                $table->dropForeign('institution_meeting_institution_id_foreign');
                $table->dropForeign('institution_meeting_meeting_id_foreign');
            });
        }

        if (Schema::hasTable('goals')) {
            Schema::table('goals', function (Blueprint $table) {
                $table->dropForeign('goals_group_id_foreign');
                $table->dropForeign('goals_padalinys_id_foreign');
            });
        }

        if (Schema::hasTable('goal_matter')) {
            Schema::table('goal_matter', function (Blueprint $table) {
                $table->dropForeign('goal_institution_matter_goal_id_foreign');
                $table->dropForeign('goal_matter_goal_id_foreign');
                $table->dropForeign('goal_institution_matter_matter_id_foreign');
                $table->dropForeign('goal_matter_matter_id_foreign');
            });
        }

        if (Schema::hasTable('dutiables')) {
            Schema::table('dutiables', function (Blueprint $table) {
                $table->dropForeign('dutiables_duty_id_foreign');
            });
        }

        if (Schema::hasTable('doing_user')) {
            Schema::table('doing_user', function (Blueprint $table) {
                $table->dropForeign('doing_user_doing_id_foreign');
                $table->dropForeign('doing_user_user_id_foreign');
            });
        }

        if (Schema::hasTable('doables')) {
            Schema::table('doables', function (Blueprint $table) {
                $table->dropForeign('doables_doing_id_foreign');
            });
        }

        if (Schema::hasTable('comments')) {
            Schema::table('comments', function (Blueprint $table) {
                $table->dropForeign('comments_user_id_foreign');
            });
        }

        if (Schema::hasTable('calendar')) {
            Schema::table('calendar', function (Blueprint $table) {
                $table->dropForeign('calendar_category_foreign');
                $table->dropForeign('calendar_padalinys_id_foreign');
            });
        }

        if (Schema::hasTable('banners')) {
            Schema::table('banners', function (Blueprint $table) {
                $table->dropForeign('banners_padalinys_id_foreign');
            });
        }

        if (Schema::hasTable('agenda_items')) {
            Schema::table('agenda_items', function (Blueprint $table) {
                $table->dropForeign('institution_meeting_matter_matter_id_foreign');
                $table->dropForeign('institution_meeting_matter_meeting_id_foreign');
            });
        }

        Schema::dropIfExists('websockets_statistics_entries');

        Schema::dropIfExists('users');

        Schema::dropIfExists('types');

        Schema::dropIfExists('typeables');

        Schema::dropIfExists('tasks');

        Schema::dropIfExists('task_user');

        Schema::dropIfExists('tags');

        Schema::dropIfExists('sharepoint_files');

        Schema::dropIfExists('sharepoint_fileables');

        Schema::dropIfExists('sessions');

        Schema::dropIfExists('saziningai_observers');

        Schema::dropIfExists('saziningai_exams');

        Schema::dropIfExists('saziningai_exam_flows');

        Schema::dropIfExists('roles');

        Schema::dropIfExists('role_has_permissions');

        Schema::dropIfExists('resources');

        Schema::dropIfExists('reservations');

        Schema::dropIfExists('reservation_user');

        Schema::dropIfExists('reservation_resource');

        Schema::dropIfExists('relationships');

        Schema::dropIfExists('relationshipables');

        Schema::dropIfExists('registrations');

        Schema::dropIfExists('registration_forms');

        Schema::dropIfExists('posts_tags');

        Schema::dropIfExists('permissions');

        Schema::dropIfExists('pages');

        Schema::dropIfExists('padaliniai');

        Schema::dropIfExists('notifications');

        Schema::dropIfExists('news');

        Schema::dropIfExists('navigation');

        Schema::dropIfExists('model_has_roles');

        Schema::dropIfExists('model_has_permissions');

        Schema::dropIfExists('meetings');

        Schema::dropIfExists('media');

        Schema::dropIfExists('matters');

        Schema::dropIfExists('main_page');

        Schema::dropIfExists('jobs');

        Schema::dropIfExists('institutions_matters');

        Schema::dropIfExists('institutions');

        Schema::dropIfExists('institution_meeting');

        Schema::dropIfExists('goals');

        Schema::dropIfExists('goal_matter');

        Schema::dropIfExists('goal_groups');

        Schema::dropIfExists('failed_jobs');

        Schema::dropIfExists('duties');

        Schema::dropIfExists('dutiables');

        Schema::dropIfExists('doings');

        Schema::dropIfExists('doing_user');

        Schema::dropIfExists('doables');

        Schema::dropIfExists('contacts');

        Schema::dropIfExists('comments');

        Schema::dropIfExists('changelog_items');

        Schema::dropIfExists('categories');

        Schema::dropIfExists('calendar');

        Schema::dropIfExists('banners');

        Schema::dropIfExists('agenda_items');

        Schema::dropIfExists('activity_log');
    }
};
