<?php

namespace Database\Seeders;

use App\Enums\InstitutionScope;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TypeSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('institution_types')->insert([
            ['id' => 1, 'title' => json_encode(['lt' => 'Programos, klubai, projektai', 'en' => '']), 'slug' => 'pkp', 'extra_attributes' => json_encode(['governance_scope' => InstitutionScope::Vusa->value])],
            ['id' => 2, 'title' => json_encode(['lt' => 'Studentų atstovų organas', 'en' => '']), 'slug' => 'studentu-atstovu-organas', 'extra_attributes' => json_encode(['governance_scope' => InstitutionScope::University->value])],
            ['id' => 3, 'title' => json_encode(['lt' => 'VU SA padalinys', 'en' => '']), 'slug' => 'padaliniai', 'extra_attributes' => json_encode(['governance_scope' => InstitutionScope::Vusa->value])],
        ]);
        DB::table('duty_types')->insert([
            ['id' => 4, 'title' => json_encode(['lt' => 'Pirmininkas', 'en' => '']), 'slug' => 'pirmininkas', 'extra_attributes' => null],
            ['id' => 5, 'title' => json_encode(['lt' => 'Prezidentas', 'en' => '']), 'slug' => 'prezidentas', 'extra_attributes' => null],
            ['id' => 6, 'title' => json_encode(['lt' => 'Koordinatorius', 'en' => '']), 'slug' => 'koordinatoriai', 'extra_attributes' => null],
            ['id' => 7, 'title' => json_encode(['lt' => 'Narys', 'en' => '']), 'slug' => 'narys', 'extra_attributes' => null],
            ['id' => 8, 'title' => json_encode(['lt' => 'Kuratorius', 'en' => '']), 'slug' => 'kuratoriai', 'extra_attributes' => null],
            ['id' => 9, 'title' => json_encode(['lt' => 'Vadovas', 'en' => '']), 'slug' => 'vadovas', 'extra_attributes' => null],
            ['id' => 10, 'title' => json_encode(['lt' => 'Studentų atstovas', 'en' => '']), 'slug' => 'studentu-atstovai', 'extra_attributes' => null],
        ]);
    }
}
