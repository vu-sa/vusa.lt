<?php

namespace Database\Seeders;

use App\Enums\InstitutionScope;
use App\Settings\DutyTypeSettings;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TypeSeeder extends Seeder
{
    public const int CHAIR_DUTY_TYPE_ID = 4;

    public const int PRESIDENT_DUTY_TYPE_ID = 5;

    public const int COORDINATOR_DUTY_TYPE_ID = 6;

    public const int MEMBER_DUTY_TYPE_ID = 7;

    public const int CURATOR_DUTY_TYPE_ID = 8;

    public const int MANAGER_DUTY_TYPE_ID = 9;

    public const int STUDENT_REPRESENTATIVE_DUTY_TYPE_ID = 10;

    public function run(): void
    {
        DB::table('institution_types')->insert([
            ['id' => 1, 'title' => json_encode(['lt' => 'Programos, klubai, projektai', 'en' => '']), 'slug' => 'pkp', 'extra_attributes' => json_encode(['governance_scope' => InstitutionScope::Vusa->value])],
            ['id' => 2, 'title' => json_encode(['lt' => 'Studentų atstovų organas', 'en' => '']), 'slug' => 'studentu-atstovu-organas', 'extra_attributes' => json_encode(['governance_scope' => InstitutionScope::University->value])],
            ['id' => 3, 'title' => json_encode(['lt' => 'VU SA padalinys', 'en' => '']), 'slug' => 'padaliniai', 'extra_attributes' => json_encode(['governance_scope' => InstitutionScope::Vusa->value])],
        ]);
        DB::table('duty_types')->insert([
            ['id' => self::CHAIR_DUTY_TYPE_ID, 'title' => json_encode(['lt' => 'Pirmininkas', 'en' => '']), 'extra_attributes' => null],
            ['id' => self::PRESIDENT_DUTY_TYPE_ID, 'title' => json_encode(['lt' => 'Prezidentas', 'en' => '']), 'extra_attributes' => null],
            ['id' => self::COORDINATOR_DUTY_TYPE_ID, 'title' => json_encode(['lt' => 'Koordinatorius', 'en' => '']), 'extra_attributes' => null],
            ['id' => self::MEMBER_DUTY_TYPE_ID, 'title' => json_encode(['lt' => 'Narys', 'en' => '']), 'extra_attributes' => null],
            ['id' => self::CURATOR_DUTY_TYPE_ID, 'title' => json_encode(['lt' => 'Kuratorius', 'en' => '']), 'extra_attributes' => null],
            ['id' => self::MANAGER_DUTY_TYPE_ID, 'title' => json_encode(['lt' => 'Vadovas', 'en' => '']), 'extra_attributes' => null],
            ['id' => self::STUDENT_REPRESENTATIVE_DUTY_TYPE_ID, 'title' => json_encode(['lt' => 'Studentų atstovas', 'en' => '']), 'extra_attributes' => null],
        ]);

        $settings = app(DutyTypeSettings::class);
        $settings->coordinator_type_id ??= self::COORDINATOR_DUTY_TYPE_ID;
        $settings->curator_type_id ??= self::CURATOR_DUTY_TYPE_ID;
        $settings->save();
    }
}
