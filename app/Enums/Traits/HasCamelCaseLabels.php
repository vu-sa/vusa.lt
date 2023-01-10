<?php

namespace App\Enums\Traits;

use Closure;

trait hasCamelCaseLabels
{
    // 
    protected static function labels(): Closure
    {
        return function (string $name) {
            // transform values from SNAKE_CASE to camelCase

            $name = strtolower($name);

            $name = preg_replace_callback('/_([a-z])/', function ($matches) {
                return strtoupper($matches[1]);
            }, $name);

            return $name;
        };
    }
}