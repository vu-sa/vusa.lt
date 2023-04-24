<?php

namespace App\Services;

use App\Models\Padalinys;
use Illuminate\Support\Carbon;

class CuratorRegistrationService
{
    public function getRegistrationMetadata()
    {
        return collect([
            'chgf' => [
                'registration_url' => null,
                'registration_launch_time' => Carbon::create(2023, 5, 1, 9, 0, 0, 'Europe/Vilnius'),
            ],
            'evaf' => [
                'registration_url' => null,
                'registration_launch_time' => null,
            ],
            'ff' => [
                'registration_url' => 'https://forms.gle/H1dYaxt5uy4rUA388',
                'registration_launch_time' => Carbon::create(2023, 4, 15, 9, 0, 0, 'Europe/Vilnius'),
            ],
            'fsf' => [
                'registration_url' => 'https://forms.gle/nQpafgsbWagQnTks8',
                'registration_launch_time' => Carbon::create(2023, 4, 25, 9, 0, 0, 'Europe/Vilnius'),
            ],
            'filf' => [
                'registration_url' => null,
                'registration_launch_time' => null,
            ],
            'gmc' => [
                'registration_url' => 'https://forms.gle/n6P8DPFmy26bbTps9',
                'registration_launch_time' => Carbon::create(2023, 4, 20, 0, 0, 0, 'Europe/Vilnius'),
            ],
            'if' => [
                'registration_url' => null,
                'registration_launch_time' => Carbon::create(2023, 5, 8, 9, 0, 0, 'Europe/Vilnius'),
            ],
            'kf' => [
                'registration_url' => null,
                'registration_launch_time' => Carbon::create(2023, 5, 1, 9, 0, 0, 'Europe/Vilnius'),
            ],
            'knf' => [
                'registration_url' => null,
                'registration_launch_time' => Carbon::create(2023, 4, 30, 9, 0, 0, 'Europe/Vilnius'),
            ],
            'mif' => [
                'registration_url' => null,
                'registration_launch_time' => Carbon::create(2023, 4, 25, 9, 0, 0, 'Europe/Vilnius'),
            ],
            'mf' => [
                'registration_url' => 'https://forms.gle/LFtYZZzaCRsDaYxMA',
                'registration_launch_time' => Carbon::create(2023, 4, 24, 9, 0, 0, 'Europe/Vilnius'),
            ],
            'sa' => [
                'registration_url' => null,
                'registration_launch_time' => Carbon::create(2023, 5, 2, 9, 0, 0, 'Europe/Vilnius'),
            ],
            'tf' => [
                'registration_url' => null,
                'registration_launch_time' => null,
            ],
            'tspmi' => [
                'registration_url' => null,
                'registration_launch_time' => Carbon::create(2023, 4, 27, 9, 0, 0, 'Europe/Vilnius'),
            ],
            'vm' => [
                'registration_url' => null,
                'registration_launch_time' => Carbon::create(2023, 5, 8, 9, 0, 0, 'Europe/Vilnius'),
            ],
        ]);
    }

    public function getRegistrationPadaliniaiWithData()
    {
        return Padalinys::query()->select('id', 'fullname', 'alias', 'type')->orderBy('fullname')->where('type', 'padalinys')->get()->map(function ($padalinys) {
            return [
                'id' => $padalinys->id,
                'fullname' => $padalinys->fullname,
                // Check if registration launch time is in the past
                'registration_url' => $this->getRegistrationMetadata()[$padalinys?->alias]['registration_launch_time']?->isPast() ? $this->getRegistrationMetadata()[$padalinys?->alias]['registration_url'] : null,
                'registration_launch_time' => $this->getRegistrationMetadata()[$padalinys?->alias]['registration_launch_time'],
            ];
        });
    }
}
