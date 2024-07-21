<?php

namespace App\Services;

use App\Models\Navigation;

class NavigationService
{
    public static function getNavigationForPublic()
    {
        $navigation = Navigation::where('lang', app()->getLocale())->orderBy('order')->get();

        // Start with root navigation elements
        $rootNavigation = $navigation->where('parent_id', 0)->values()->toArray();

        // Build children on root navigation elements
        for ($i = 0; $i < count($rootNavigation); $i++) {

            // The structure that the UI uses is that the root navigation elements have a 'links' property which is an array of max. 3 arrays.
            // Each array of the links array represents a column in the navigation.
            // The information about which column the link should be in is stored in the 'extra_attributes->column' property of the link.
            $rootNavigation[$i]['links'] = [];

            // Get immediate children of root navigation element
            $children = $navigation->where('parent_id', $rootNavigation[$i]['id'])->values()->toArray();

            // Expand extra_attributes to own keys to make it easier to work with
            // Other data in the extra_attributes array will be used in the UI
            foreach ($children as $key => $child) {
                $extraAttributes = $child['extra_attributes'];
                unset($child['extra_attributes']);

                // If extra_attributes is null, continue
                if ($extraAttributes === null) {
                    continue;
                }

                // Update the child with the extra attributes
                foreach ($extraAttributes as $extraKey => $extraValue) {
                    $child[$extraKey] = $extraValue;
                }

                // Update the child by overwriting the old child
                $children[$key] = $child;
            }

            // Set the links of the root navigation by columns
            for ($j = 1; $j <= 3; $j++) {

                // Push array to root links, where extra_attributes['column'] == $j
                $rootNavigation[$i]['links'][] = array_filter($children, function ($child) use ($j) {

                    // Also check if the column is not set, then it should be in the first column
                    if (! isset($child['column'])) {
                        return $j == 1;
                    }

                    return $child['column'] == $j;
                });

                $rootNavigation[$i]['links'][$j - 1] = array_values($rootNavigation[$i]['links'][$j - 1]);
            }

            // Remove empty arrays
            $rootNavigation[$i]['links'] = array_filter($rootNavigation[$i]['links']);

            // Add column count immediately for the front end
            $rootNavigation[$i]['cols'] = count($rootNavigation[$i]['links']);
        }

        return $rootNavigation;
    }
}
