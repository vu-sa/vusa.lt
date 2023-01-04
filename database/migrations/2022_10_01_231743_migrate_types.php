<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Doing;
use App\Models\Duty;
use App\Models\DutyInstitution;
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
                'model_type' => 'App\Models\DutyInstitution',
                'description' => $type->description,
                'slug' => $type->alias,
                'extra_attributes' => $type->attributes,
            ]);

            $dutyInstitutions = DB::table('duties_institutions')->where('type_id', $type->id)->get();
            foreach ($dutyInstitutions as $dutyInstitution) {
                DB::table('typeables')->insert([
                    'type_id' => $new_type->id,
                    'typeable_id' => $dutyInstitution->id,
                    'typeable_type' => 'App\Models\DutyInstitution'
                ]);
            }
        });

        DB::table('duties_types')->get()->each(function ($type) {
            $new_type = Type::create([
                'title' => $type->name,
                'model_type' => 'App\Models\Duty',
                'description' => $type->description,
            ]);

            $duties = Duty::withTrashed()->where('type_id', $type->id)->get();
            foreach ($duties as $duty) {
                DB::table('typeables')->insert([
                    'type_id' => $new_type->id,
                    'typeable_id' => $duty->id,
                    'typeable_type' => 'App\Models\Duty'
                ]);
            }
        });

        DB::table('doing_types')->get()->each(function ($type) {
            $new_type = Type::create([
                'title' => $type->title,
                'model_type' => Doing::class,
            ]);

            $doings = Doing::withTrashed()->where('doing_type_id', $type->id)->get();
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
