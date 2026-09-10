<?php

namespace App\Enums;

enum SupportRequestVisibility: string
{
    case Private = 'private';
    case Roles = 'roles';
    case Public = 'public';

    public function label(): string
    {
        return match ($this) {
            self::Private => __('Privatu'),
            self::Roles => __('Pasirinktoms rolėms'),
            self::Public => __('Visiems prisijungusiems'),
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Private => __('Matoma tik pranešėjui ir sistemos administratoriams.'),
            self::Roles => __('Matoma nurodytų rolių nariams ir sistemos administratoriams.'),
            self::Public => __('Matoma visiems prie sistemos prisijungusiems nariams.'),
        };
    }
}
