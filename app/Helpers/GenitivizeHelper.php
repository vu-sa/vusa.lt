<?php

namespace App\Helpers;

class GenitivizeHelper
{
    public static function genitivize($name)
    {
        if ($name === null) {
            return '';
        }

        if (app()->getLocale() !== 'lt') {
            return $name;
        }

        $name = preg_replace('/a$/', 'os', $name);
        $name = preg_replace('/as$/', 'o', $name);
        $name = preg_replace('/ė$/', 'ės', $name);
        $name = preg_replace('/is$/', 'io', $name);
        $name = preg_replace('/iai$/', 'ių', $name);
        $name = preg_replace('/ė$/', 'ės', $name);

        return $name;
    }

    public static function genitivizeEveryWord($name)
    {
        if ($name === null) {
            return '';
        }

        $words = explode(' ', $name);
        $genitivizedWords = array_map([self::class, 'genitivize'], $words);

        return implode(' ', $genitivizedWords);
    }
}
