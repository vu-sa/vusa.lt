<?php

use Doctrine\DBAL\Schema\Schema as SchemaSchema;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // change comments schema to use user ulids
        Schema::table('comments', function (Blueprint $table) {
            // drop foreign key
            $table->dropForeign(['user_id']);
            $table->char('id', 26)->change();
            $table->char('parent_id', 26)->change();
        });

        Schema::table('comments', function (Blueprint $table) {
            $table->char('user_id', 26)->change();
            $table->char('commentable_id', 26)->change();
        });

        Schema::table('task_user', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('task_user', function (Blueprint $table) {
            $table->char('user_id', 26)->change();
        });

        Schema::table('banners', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });

        Schema::table('calendar', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
            $table->renameColumn('attributes', 'extra_attributes');
        });

        Schema::table('duties_users', function (Blueprint $table) {
            $table->renameColumn('user_id', 'dutiable_id');
            $table->string('dutiable_type')->default('User')->after('user_id');
        });

        Schema::rename('duties_users', 'dutiables');

        DB::table('dutiables')->whereIn('duty_id', [256, 268, 269, 273, 275])->delete();

        Schema::table('dutiables', function (Blueprint $table) {
            $table->string('dutiable_type')->default(null)->change();
            $table->dropForeign('duties_users_user_id_foreign');
            $table->renameColumn('attributes', 'extra_attributes');
            $table->dropColumn('id');
            $table->primary(['dutiable_id', 'dutiable_type', 'duty_id']);
            $table->dropForeign('duties_users_duty_id_foreign');
        });

        Schema::table('main_page', function (Blueprint $table) {
            $table->dropForeign('main_page_user_id_foreign');
            $table->dropColumn('user_id');
        });

        Schema::table('news', function (Blueprint $table) {
            $table->dropForeign('news_user_id_foreign');
            $table->dropColumn('user_id');
        });

        Schema::table('pages', function (Blueprint $table) {
            $table->dropForeign('pages_user_id_foreign');
            $table->dropColumn('user_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->char('id', 26)->change();
        });

        Schema::table('model_has_roles', function (Blueprint $table) {
            $table->char('model_id', 26)->change();
        });

        Schema::table('model_has_roles', function (Blueprint $table) {
            $table->char('model_id', 26)->change();
        });

        Schema::table('dutiables', function (Blueprint $table) {
            $table->char('dutiable_id', 26)->change();
        });

        Schema::table('task_user', function (Blueprint $table) {
            $table->char('user_id', 26)->change();
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->char('notifiable_id', 26)->change();
        });
        
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['two_factor_recovery_codes', 'two_factor_secret']);
            $table->softDeletes();
    
            DB::table('users')->orderBy('id')->chunkById(100, function ($users) {
                foreach ($users as $user) {
                    // make ulid lowercase
                    $ulid = Str::lower(Str::ulid());
                    
                    DB::table('users')
                        ->where('id', $user->id)
                        ->update(['id' => $ulid]);

                    DB::table('dutiables')
                        ->where('dutiable_id', $user->id)
                        ->update(['dutiable_id' => $ulid]);

                    DB::table('comments')
                        ->where('user_id', $user->id)
                        ->update(['user_id' => $ulid]);

                    DB::table('model_has_roles')
                        ->where('model_id', $user->id)
                        ->update(['model_id' => $ulid]);

                    DB::table('task_user')
                        ->where('user_id', $user->id)
                        ->update(['user_id' => $ulid]);

                    DB::table('notifications')
                        ->where('notifiable_id', $user->id)
                        ->update(['notifiable_id' => $ulid]);
                }
            });
        });

        Schema::table('comments', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users');
            $table->softDeletes();
        });
        
        // create contacts table for contacts that are not users
        Schema::create('contacts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('profile_photo_path')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->softDeletes();
        });

        Schema::rename('duties_institutions', 'institutions');

        Schema::table('questions', function (Blueprint $table) {
            // drop foreign key
            $table->dropForeign(['question_group_id']);
            $table->dropForeign(['institution_id']);
        });

        Schema::dropIfExists('doing_question');
        Schema::dropIfExists('question_groups');
        Schema::dropIfExists('questions');

        Schema::table('duties', function (Blueprint $table) {
            // drop foreign key
            $table->dropForeign(['institution_id']);
            $table->renameColumn('attributes', 'extra_attributes');
        });

        Schema::table('duties', function (Blueprint $table) {
            $table->char('institution_id', 26)->change();
        });

        Schema::table('institutions', function (Blueprint $table) {
            // rename column
            $table->renameColumn('pid', 'parent_id');
            $table->unsignedInteger('padalinys_id')->comment('')->change();
            $table->renameColumn('attributes', 'extra_attributes');
        });

        Schema::table('institutions', function (Blueprint $table) {
            $table->char('id', 26)->change();
            $table->char('parent_id', 26)->nullable()->change();
        });

        Schema::table('institutions', function (Blueprint $table) {
            $table->softDeletes();
            $table->dropForeign('duties_institutions_padalinys_id_foreign');
            $table->renameIndex('duties_institutions_pid_alias_unique', 'institutions_parent_id_alias_unique');
            $table->dropIndex('duties_institutions_alias_index');
            // make $table->unsignedInteger('padalinys_id') foreign key on padalinys
            $table->foreign('padalinys_id')->references('id')->on('padaliniai');
        });

        DB::table('institutions')->orderBy('id')->chunkById(100, function ($institutions) {
            foreach ($institutions as $institution) {
                $ulid = Str::lower(Str::ulid());
                
                DB::table('institutions')
                    ->where('id', $institution->id)
                    ->update(['id' => $ulid]);

                DB::table('institutions')
                    ->where('parent_id', $institution->id)
                    ->update(['parent_id' => $ulid]);

                DB::table('duties')
                    ->where('institution_id', $institution->id)
                    ->update(['institution_id' => $ulid]);
            }
        }, 'id');

        Schema::create('institution_matters', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->char('institution_id', 26);
            $table->foreign('institution_id')->references('id')->on('institutions');
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->softDeletes();
        });

        Schema::create('institution_meetings', function (Blueprint $table) {
            $table->ulid('id')->primary();
            // $table->string('title');
            $table->text('description')->nullable();
            $table->dateTime('start_time');
            $table->dateTime('end_time')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->softDeletes();
        });

        Schema::create('goal_groups', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->softDeletes();
        });

        Schema::create('goals', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->char('group_id', 26);
            $table->foreign('group_id')->references('id')->on('goal_groups');
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->softDeletes();
        });

        Schema::create('institution_meeting_matter', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlId('meeting_id')->constrained('institution_meetings', 'id');
            $table->foreignUlId('matter_id')->constrained('institution_matters', 'id');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        Schema::create('goal_institution_matter', function (Blueprint $table) {
            $table->foreignUlid('goal_id')->constrained('goals', 'id');
            $table->foreignUlid('matter_id')->constrained('institution_matters', 'id');
            $table->primary(['goal_id', 'matter_id']);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        Schema::table('activity_log', function (Blueprint $table) {
            $table->string('subject_id', 26)->nullable()->change();
            $table->string('causer_id', 26)->nullable()->change();
        });

        Schema::table('doings', function (Blueprint $table) {
            $table->ulid('id')->change();
            $table->softDeletes();
        });

        Schema::create('doables', function (Blueprint $table) {
            $table->ulidMorphs('doable');
            $table->foreignUlid('doing_id')->constrained('doings', 'id');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        Schema::table('doables', function (Blueprint $table) {
            $table->primary(['doable_id', 'doable_type', 'doing_id']);
            $table->dropIndex(['doable_type', 'doable_id']);
        });

        Schema::table('dutiables', function (Blueprint $table) {
            $table->char('duty_id', 26)->change();
        });

        Schema::table('duties', function (Blueprint $table) {
            $table->char('id', 26)->change();
            $table->softDeletes();
        });

        DB::table('duties')->orderBy('id')->chunkById(100, function ($duties) {
            foreach ($duties as $duty) {
                $ulid = Str::lower(Str::ulid());
                
                DB::table('duties')
                    ->where('id', $duty->id)
                    ->update(['id' => $ulid]);

                DB::table('dutiables')
                    ->where('duty_id', $duty->id)
                    ->update(['duty_id' => $ulid]);
            }
        }, 'id');

        Schema::table('dutiables', function (Blueprint $table) {
            $table->foreign('duty_id')->references('id')->on('duties');
        });

        Schema::table('sharepoint_documents', function(Blueprint $table) {
            $table->char('documentable_id', 26)->change();
        });

        Schema::table('task_user', function (Blueprint $table) {
            $table->dropForeign(['task_id']);
        });

        Schema::table('tasks', function (Blueprint $table) {
            $table->char('id', 26)->change();
            $table->softDeletes();
        });

        Schema::table('task_user', function (Blueprint $table) {
            $table->char('task_id', 26)->change();
            $table->foreign('task_id')->references('id')->on('tasks');
        });

        Schema::table('typeables', function (Blueprint $table) {
            $table->char('typeable_id', 26)->change();
        });

        Schema::table('types', function (Blueprint $table) {
            $table->softDeletes();
        });

        DB::table('dutiables')->where('dutiable_type', 'User')->update(['dutiable_type' => 'App\\Models\\User']);

        // invalidate all sessions
        DB::table('sessions')->update(['user_id' => null]);

        Schema::table('sessions', function (Blueprint $table) {
            $table->char('user_id', 26)->nullable()->change();
        });

        Schema::table('relationshipables', function (Blueprint $table) {
            $table->string('relationshipable_id', 26)->change();
            $table->string('related_model_id', 26)->change();
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
};
