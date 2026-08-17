<?php

namespace Database\Seeders;

use App\Models\Duty;
use App\Models\Institution;
use App\Support\MorphMap;
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
                // Institution::class Types
                ['title' => json_encode(['lt' => 'Programos, klubai, projektai', 'en' => '']), 'slug' => 'pkp', 'model_type' => MorphMap::alias(Institution::class)],
                ['title' => json_encode(['lt' => 'Studentų atstovų organas', 'en' => '']), 'slug' => 'studentu-atstovu-organas', 'model_type' => MorphMap::alias(Institution::class)],
                ['title' => json_encode(['lt' => 'VU SA padalinys', 'en' => '']), 'slug' => 'padaliniai', 'model_type' => MorphMap::alias(Institution::class)],
                // Duty::class Types
                ['title' => json_encode(['lt' => 'Pirmininkas', 'en' => '']), 'slug' => 'pirmininkas', 'model_type' => MorphMap::alias(Duty::class)],
                ['title' => json_encode(['lt' => 'Prezidentas', 'en' => '']), 'slug' => 'prezidentas', 'model_type' => MorphMap::alias(Duty::class)],
                ['title' => json_encode(['lt' => 'Koordinatorius', 'en' => '']), 'slug' => 'koordinatoriai', 'model_type' => MorphMap::alias(Duty::class)],
                ['title' => json_encode(['lt' => 'Narys', 'en' => '']), 'slug' => 'narys', 'model_type' => MorphMap::alias(Duty::class)],
                ['title' => json_encode(['lt' => 'Kuratorius', 'en' => '']), 'slug' => 'kuratoriai', 'model_type' => MorphMap::alias(Duty::class)],
                ['title' => json_encode(['lt' => 'Vadovas', 'en' => '']), 'slug' => 'vadovas', 'model_type' => MorphMap::alias(Duty::class)],
                ['title' => json_encode(['lt' => 'Studentų atstovas', 'en' => '']), 'slug' => 'studentu-atstovai', 'model_type' => MorphMap::alias(Duty::class)],
                // Meeting types are now handled via MeetingType enum in App\Enums\MeetingType
            ]
        );
    }
}
