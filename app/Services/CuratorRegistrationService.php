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
                'registration_url' => 'https://docs.google.com',
                'registration_launch_time' => Carbon::create(2023, 4, 15, 0, 0, 0, 'Europe/Vilnius'),
            ],
            'evaf' => [
                'registration_url' => 'https://docs.google.com',
                'registration_launch_time' => Carbon::create(2023, 4, 15, 0, 0, 0, 'Europe/Vilnius'),
            ],
            'ff' => [
                'registration_url' => 'https://docs.google.com',
                'registration_launch_time' => Carbon::create(2023, 4, 15, 0, 0, 0, 'Europe/Vilnius'),
            ],
            'fsf' => [
                'registration_url' => 'https://docs.google.com',
                'registration_launch_time' => Carbon::create(2023, 4, 15, 0, 0, 0, 'Europe/Vilnius'),
            ],
            'filf' => [
                'registration_url' => 'https://docs.google.com',
                'registration_launch_time' => Carbon::create(2023, 4, 15, 0, 0, 0, 'Europe/Vilnius'),
            ],
            'gmc' => [
                'registration_url' => 'https://docs.google.com',
                'registration_launch_time' => Carbon::create(2023, 4, 15, 0, 0, 0, 'Europe/Vilnius'),
            ],
            'if' => [
                'registration_url' => 'https://docs.google.com',
                'registration_launch_time' => Carbon::create(2023, 4, 15, 0, 0, 0, 'Europe/Vilnius'),
            ],
            'kf' => [
                'registration_url' => 'https://docs.google.com',
                'registration_launch_time' => Carbon::create(2023, 4, 15, 0, 0, 0, 'Europe/Vilnius'),
            ],
            'knf' => [
                'registration_url' => 'https://docs.google.com',
                'registration_launch_time' => Carbon::create(2023, 4, 15, 0, 0, 0, 'Europe/Vilnius'),
            ],
            'mif' => [
                'registration_url' => 'https://docs.google.com',
                'registration_launch_time' => Carbon::create(2023, 4, 15, 0, 0, 0, 'Europe/Vilnius'),
            ],
            'mf' => [
                'registration_url' => 'https://docs.google.com',
                'registration_launch_time' => Carbon::create(2023, 4, 15, 0, 0, 0, 'Europe/Vilnius'),
            ],
            'sa' => [
                'registration_url' => 'https://docs.google.com',
                'registration_launch_time' => Carbon::create(2023, 4, 15, 0, 0, 0, 'Europe/Vilnius'),
            ],
            'tf' => [
                'registration_url' => 'https://docs.google.com',
                'registration_launch_time' => Carbon::create(2023, 4, 15, 0, 0, 0, 'Europe/Vilnius'),
            ],
            'tspmi' => [
                'registration_url' => 'https://docs.google.com',
                'registration_launch_time' => Carbon::create(2023, 4, 15, 0, 0, 0, 'Europe/Vilnius'),
            ],
            'vm' => [
                'registration_url' => 'https://docs.google.com',
                'registration_launch_time' => Carbon::create(2023, 4, 15, 0, 0, 0, 'Europe/Vilnius'),
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
                'registration_url' => $this->getRegistrationMetadata()[$padalinys?->alias]['registration_launch_time']->isPast() ? $this->getRegistrationMetadata()[$padalinys?->alias]['registration_url'] : null,
                'registration_launch_time' => $this->getRegistrationMetadata()[$padalinys?->alias]['registration_launch_time'],
            ];
        });
    }
}
