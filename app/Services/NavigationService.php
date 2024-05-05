<?php

namespace App\Services;

use App\Models\Navigation;
use App\Models\Padalinys;

class NavigationService
{
    public static function getNavigationForPublic()
    {
        $vusa = Padalinys::where('shortname', 'VU SA')->first();

        $navigation = Navigation::where([['padalinys_id', $vusa->id], ['lang', app()->getLocale()]])->orderBy('order')->get();

        $rootNavigation = $navigation->where('parent_id', 0)->values()->toArray();

        for ($i = 0; $i < count($rootNavigation); $i++) {
            // Make array of arrays of links, by columns in extra_attributes
            $rootNavigation[$i]['links'] = [];

            $children = $navigation->where('parent_id', $rootNavigation[$i]['id'])->values()->toArray();

            //# Expand extra_attributes to own keys
            foreach ($children as $key => $child) {
                $extraAttributes = $child['extra_attributes'];
                unset($child['extra_attributes']);

                // If extra_attributes is null set to empty array
                if ($extraAttributes === null) {
                    $extraAttributes = [];
                }

                foreach ($extraAttributes as $extraKey => $extraValue) {
                    $child[$extraKey] = $extraValue;
                }

                $children[$key] = $child;
            }

            for ($j = 1; $j <= 3; $j++) {
                // Push array to root links, where extra_attributes['column'] == $j
                $rootNavigation[$i]['links'][] = array_filter($children, function ($child) use ($j) {
                    if (! isset($child['column'])) {
                        return $j == 1;
                    }

                    return $child['column'] == $j;
                });

                // To values
                $rootNavigation[$i]['links'][$j - 1] = array_values($rootNavigation[$i]['links'][$j - 1]);
            }

            // Remove empty arrays
            $rootNavigation[$i]['links'] = array_filter($rootNavigation[$i]['links']);

            // Add column count
            $rootNavigation[$i]['cols'] = count($rootNavigation[$i]['links']);
        }

        return $rootNavigation;
    }
}
