<?php

namespace Database\Seeders;

use App\Models\Doing;
use App\Models\Duty;
use App\Models\Institution;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('types')->insert(
            [
                ['title' => 'Programos, klubai, projektai', 'slug' => 'pkp', 'model_type' => Institution::class],
                ['title' => 'Studentų atstovų organas', 'slug' => 'studentu-atstovu-organas', 'model_type' => Institution::class],
                ['title' => 'VU SA padalinys', 'slug' => 'padaliniai', 'model_type' => Institution::class],
                ['title' => 'Pirmininkas', 'slug' => 'pirmininkas', 'model_type' => Duty::class],
                ['title' => 'Prezidentas', 'slug' => 'prezidentas', 'model_type' => Duty::class],
                ['title' => 'Koordinatorius', 'slug' => 'koordinatoriai', 'model_type' => Duty::class],
                ['title' => 'Narys', 'slug' => 'narys', 'model_type' => Duty::class],
                ['title' => 'Kuratorius', 'slug' => 'kuratoriai', 'model_type' => Duty::class],
                ['title' => 'Vadovas', 'slug' => 'vadovas', 'model_type' => Duty::class],
                ['title' => 'Studentų atstovas', 'slug' => 'studentu-atstovai', 'model_type' => Duty::class],
                ['title' => 'Susitikimas', 'slug' => 'susitikimas', 'model_type' => Doing::class],
                ['title' => 'Laiškas', 'slug' => 'laiskas', 'model_type' => Doing::class],
                ['title' => 'El. apklausa', 'slug' => 'el-apklausa', 'model_type' => Doing::class],
                ['title' => 'Focus grupė', 'slug' => 'focus-grupe', 'model_type' => Doing::class],
            ]
        );
    }
}
