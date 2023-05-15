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
                'registration_url' => 'https://forms.gle/pQwZjUeKFAGUvxic9',
                'registration_launch_time' => Carbon::create(2023, 5, 1, 9, 0, 0, 'Europe/Vilnius'),
                'registration_end_time' => Carbon::create(2023, 5, 17, 9, 0, 0, 'Europe/Vilnius'),
            ],
            'evaf' => [
                'registration_url' => 'https://forms.gle/ADUXCvpYHnf17UwDA',
                'registration_launch_time' => Carbon::create(2023, 5, 8, 9, 0, 0, 'Europe/Vilnius'),
                'registration_end_time' => Carbon::create(2023, 5, 18, 9, 0, 0, 'Europe/Vilnius'),
            ],
            'ff' => [
                'registration_url' => 'https://forms.gle/H1dYaxt5uy4rUA388',
                'registration_launch_time' => Carbon::create(2023, 4, 15, 9, 0, 0, 'Europe/Vilnius'),
                'registration_end_time' => Carbon::create(2023, 5, 1, 9, 0, 0, 'Europe/Vilnius'),
            ],
            'fsf' => [
                'registration_url' => 'https://forms.gle/nQpafgsbWagQnTks8',
                'registration_launch_time' => Carbon::create(2023, 4, 25, 9, 0, 0, 'Europe/Vilnius'),
                'registration_end_time' => Carbon::create(2023, 5, 7, 9, 0, 0, 'Europe/Vilnius'),
            ],
            'filf' => [
                'registration_url' => 'https://forms.gle/ktxDD4Aqjqx5CE6U8',
                'registration_launch_time' => Carbon::create(2023, 4, 27, 9, 0, 0, 'Europe/Vilnius'),
                'registration_end_time' => Carbon::create(2023, 5, 14, 9, 0, 0, 'Europe/Vilnius'),
            ],
            'gmc' => [
                'registration_url' => 'https://forms.gle/n6P8DPFmy26bbTps9',
                'registration_launch_time' => Carbon::create(2023, 4, 20, 0, 0, 0, 'Europe/Vilnius'),
                'registration_end_time' => Carbon::create(2023, 5, 4, 0, 0, 0, 'Europe/Vilnius'),
            ],
            'if' => [
                'registration_url' => null,
                'registration_launch_time' => Carbon::create(2023, 5, 8, 9, 0, 0, 'Europe/Vilnius'),
                'registration_end_time' => Carbon::create(2023, 5, 17, 9, 0, 0, 'Europe/Vilnius'),
            ],
            'kf' => [
                'registration_url' => 'https://forms.gle/7f3EfG4GaUmvcsc5A',
                'registration_launch_time' => Carbon::create(2023, 5, 1, 9, 0, 0, 'Europe/Vilnius'),
                'registration_end_time' => Carbon::create(2023, 5, 15, 9, 0, 0, 'Europe/Vilnius'),
            ],
            'knf' => [
                'registration_url' => 'https://forms.gle/jfb2FWX5KDLnAuEr9',
                'registration_launch_time' => Carbon::create(2023, 4, 30, 9, 0, 0, 'Europe/Vilnius'),
                'registration_end_time' => Carbon::create(2023, 5, 11, 9, 0, 0, 'Europe/Vilnius'),
            ],
            'mif' => [
                'registration_url' => 'https://docs.google.com/forms/d/e/1FAIpQLSenmIf1d4IFcSwxC0E9rMFgNWU5RV-kE7cC67WdXoc0hxZXig/viewform?usp=sf_link',
                'registration_launch_time' => Carbon::create(2023, 4, 25, 9, 0, 0, 'Europe/Vilnius'),
                'registration_end_time' => Carbon::create(2023, 5, 7, 9, 0, 0, 'Europe/Vilnius'),
            ],
            'mf' => [
                'registration_url' => 'https://forms.gle/LFtYZZzaCRsDaYxMA',
                'registration_launch_time' => Carbon::create(2023, 4, 24, 9, 0, 0, 'Europe/Vilnius'),
                'registration_end_time' => Carbon::create(2023, 5, 7, 9, 0, 0, 'Europe/Vilnius'),
            ],
            'sa' => [
                'registration_url' => 'https://forms.gle/MePJ3Dw21ZCS94wNA',
                'registration_launch_time' => Carbon::create(2023, 5, 2, 9, 0, 0, 'Europe/Vilnius'),
                'registration_end_time' => Carbon::create(2023, 5, 20, 9, 0, 0, 'Europe/Vilnius'),
            ],
            'tf' => [
                'registration_url' => null,
                'registration_launch_time' => null,
                'registration_end_time' => null,
            ],
            'tspmi' => [
                'registration_url' => 'https://forms.gle/Lq1DNMzvWRFfyiA89',
                'registration_launch_time' => Carbon::create(2023, 4, 27, 9, 0, 0, 'Europe/Vilnius'),
                'registration_end_time' => Carbon::create(2023, 5, 14, 9, 0, 0, 'Europe/Vilnius'),
            ],
            'vm' => [
                'registration_url' => 'https://forms.gle/qGxyHvNwA4GDn38y6',
                'registration_launch_time' => Carbon::create(2023, 5, 1, 9, 0, 0, 'Europe/Vilnius'),
                'registration_end_time' => Carbon::create(2023, 5, 14, 9, 0, 0, 'Europe/Vilnius'),
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
                'registration_end_time' => $this->getRegistrationMetadata()[$padalinys?->alias]['registration_end_time'],
            ];
        });
    }
}
