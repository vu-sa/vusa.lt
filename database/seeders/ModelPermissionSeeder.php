<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Enums\CRUDEnum;
use App\Enums\ModelEnum;
use App\Enums\PermissionScopeEnum;
use App\Models\Permission;

class ModelPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $permissionsToCreate = [];

        foreach (ModelEnum::toLabels() as $model) {
            $pluralizedModel = $this->simplePluralize($model);
            
            foreach (CRUDEnum::toLabels() as $crud) {
                foreach (PermissionScopeEnum::toLabels() as $scope) {
                    $permissionsToCreate[] = $pluralizedModel . '.' . $crud . '.' . $scope;
                }
            }
        }

        foreach ($permissionsToCreate as $permission) {
            Permission::create([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }
    }

    private function simplePluralize ($word)
    {
        $lastLetter = substr($word, -1);

        if ($lastLetter == 'y') {
            return substr($word, 0, -1) . 'ies';
        } 

        // if word is navigation or calendar then we don't want to pluralize it
        if ($word == 'navigation' || $word == 'calendar' || $word == 'news') {
            return $word;
        }

        return $word . 's';
    }
}
