<?php

namespace App\Helpers;

class AddressivizeHelper
{
    public static function addressivize($name)
    {
        if ($name === null) {
            return '';
        }

        // check if app locale is not lithuanian
        if (app()->getLocale() !== 'lt') {
            return $name;
        }

        $name = preg_replace('/as$/', 'ai', $name);
        $name = preg_replace('/ė$/', 'e', $name);
        $name = preg_replace('/is$/', 'i', $name);
        $name = preg_replace('/us$/', 'au', $name);
        $name = preg_replace('/ys$/', 'y', $name);

        return $name;
    }

    public static function addressivizeEveryWord($name)
    {
        if ($name === null) {
            return '';
        }

        $words = explode(' ', $name);
        $addressivizedWords = array_map([self::class, 'addressivize'], $words);

        return implode(' ', $addressivizedWords);
    }
}
