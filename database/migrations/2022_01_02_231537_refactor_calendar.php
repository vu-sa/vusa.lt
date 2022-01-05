<?php

use App\Models\Calendar;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

class RefactorCalendar extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('calendar', function (Blueprint $table) {
            $table->increments('id')->change();
            $table->string('title')->change();
            $table->dateTimeTz('date')->change();
            $table->dropColumn('time');
            $table->renameColumn('descr', 'description');
            $table->renameColumn('classname', 'category');
            $table->renameColumn('editor', 'user_id');
            $table->dropColumn('badge');
        });

        DB::table('calendar')->update(['user_id' => 56]);

        Schema::table('calendar', function (Blueprint $table) {
            $table->string('category')->nullable()->change();
            $table->text('description')->nullable()->change();
            $table->unsignedInteger('user_id')->nullable()->change();
            $table->foreign('user_id')->references('id')->on('users');
            $table->unsignedInteger('padalinys_id')->default(16)->after('user_id');
            $table->foreign('padalinys_id')->references('id')->on('padaliniai');
        });

        $agenda = DB::table('agenda')->select('date', 'title', 'description', 'classname', 'editor')->get();

        foreach ($agenda as $item) {
            $date = $item->date;
            $title = $item->title;
            $description = $item->description;
            $category = $item->classname;

            DB::table('calendar')->insert([
                'date' => $date,
                'title' => $title,
                'description' => $description,
                'category' => $category
            ]);
        }

        Schema::dropIfExists('agenda');
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
