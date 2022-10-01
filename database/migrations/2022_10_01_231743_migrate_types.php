<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\ContentType;
use App\Models\Doing;
use App\Models\Duty;
use App\Models\DutyInstitution;
use App\Models\DutyType;
use App\Models\Type;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // migrate duties institutions types
        DB::table('duties_institutions_types')->get()->each(function ($type) {
            $new_type = Type::create([
                'title' => $type->name,
                'model_type' => DutyInstitution::class,
                'description' => $type->description,
                'slug' => $type->alias,
                'extra_attributes' => $type->attributes,
            ]);

            $dutyInstitutions = DutyInstitution::where('type_id', $type->id)->get();
            foreach ($dutyInstitutions as $dutyInstitution) {
                $dutyInstitution->types()->attach($new_type->id);
                $dutyInstitution->save();
            }
        });

        DB::table('duties_types')->get()->each(function ($type) {
            $new_type = Type::create([
                'title' => $type->name,
                'model_type' => Duty::class,
                'parent_id' => $type->pid,
                'description' => $type->description,
            ]);

            $duties = Duty::where('type_id', $type->id)->get();
            foreach ($duties as $duty) {
                $duty->types()->attach($new_type->id);
                $duty->save();
            }
        });

        DB::table('doing_types')->get()->each(function ($type) {
            $new_type = Type::create([
                'title' => $type->title,
                'model_type' => Doing::class,
            ]);

            $doings = Doing::where('doing_type_id', $type->id)->get();
            foreach ($doings as $doing) {
                $doing->types()->attach($new_type->id);
                $doing->save();
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('typeables')->truncate();
        DB::table('types')->truncate();
    }
};
