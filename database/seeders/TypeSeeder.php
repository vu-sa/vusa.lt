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
                ['title' => json_encode([ 'lt' => 'Programos, klubai, projektai', 'en' => '' ]), 'slug' => 'pkp', 'model_type' => Institution::class],
                ['title' => json_encode([ 'lt' => 'Studentų atstovų organas', 'en' => '' ]), 'slug' => 'studentu-atstovu-organas', 'model_type' => Institution::class],
                ['title' => json_encode([ 'lt' => 'VU SA padalinys', 'en' => '' ]), 'slug' => 'padaliniai', 'model_type' => Institution::class],
                ['title' => json_encode([ 'lt' => 'Pirmininkas', 'en' => '' ]), 'slug' => 'pirmininkas', 'model_type' => Duty::class],
                ['title' => json_encode([ 'lt' => 'Prezidentas', 'en' => '' ]), 'slug' => 'prezidentas', 'model_type' => Duty::class],
                ['title' => json_encode([ 'lt' => 'Koordinatorius', 'en' => '' ]), 'slug' => 'koordinatoriai', 'model_type' => Duty::class],
                ['title' => json_encode([ 'lt' => 'Narys', 'en' => '' ]), 'slug' => 'narys', 'model_type' => Duty::class],
                ['title' => json_encode([ 'lt' => 'Kuratorius', 'en' => '' ]), 'slug' => 'kuratoriai', 'model_type' => Duty::class],
                ['title' => json_encode([ 'lt' => 'Vadovas', 'en' => '' ]), 'slug' => 'vadovas', 'model_type' => Duty::class],
                ['title' => json_encode([ 'lt' => 'Studentų atstovas', 'en' => '' ]), 'slug' => 'studentu-atstovai', 'model_type' => Duty::class],
                ['title' => json_encode([ 'lt' => 'Susitikimas', 'en' => '' ]), 'slug' => 'susitikimas', 'model_type' => Doing::class],
                ['title' => json_encode([ 'lt' => 'Laiškas', 'en' => '' ]), 'slug' => 'laiskas', 'model_type' => Doing::class],
                ['title' => json_encode([ 'lt' => 'El. apklausa', 'en' => '' ]), 'slug' => 'el-apklausa', 'model_type' => Doing::class],
                ['title' => json_encode([ 'lt' => 'Focus grupė', 'en' => '' ]), 'slug' => 'focus-grupe', 'model_type' => Doing::class],
            ]
        );
    }
}
