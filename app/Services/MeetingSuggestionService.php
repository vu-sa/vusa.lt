<?php

namespace App\Services;

use App\Models\Institution;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class MeetingSuggestionService
{
    /**
     * Get suggested meeting times based on historical meeting patterns
     * 
     * @param Institution $institution
     * @param int $limit Number of suggestions to return
     * @return array Array of suggested times with weekday and time info
     */
    public function getSuggestedMeetingTimes(Institution $institution, int $limit = 4): array
    {
        // Get recent meetings (last 6 months) to analyze patterns
        $recentMeetings = $institution->meetings()
            ->where('start_time', '>=', now()->subMonths(6))
            ->orderBy('start_time', 'desc')
            ->get(['start_time']);

        if ($recentMeetings->isEmpty()) {
            return $this->getDefaultSuggestedTimes($limit);
        }

        // Analyze patterns
        $timePatterns = [];
        $dayPatterns = [];

        foreach ($recentMeetings as $meeting) {
            $startTime = $meeting->start_time;
            $dayOfWeek = $startTime->format('N'); // 1-7 (Monday-Sunday)
            $timeOfDay = $startTime->format('H:i');
            
            // Count occurrences of each time
            $timeKey = $timeOfDay;
            if (!isset($timePatterns[$timeKey])) {
                $timePatterns[$timeKey] = 0;
            }
            $timePatterns[$timeKey]++;
            
            // Count occurrences of each day
            if (!isset($dayPatterns[$dayOfWeek])) {
                $dayPatterns[$dayOfWeek] = 0;
            }
            $dayPatterns[$dayOfWeek]++;
        }

        // Sort by frequency
        arsort($timePatterns);
        arsort($dayPatterns);

        // Get most common times and days
        $topTimes = array_keys(array_slice($timePatterns, 0, $limit, true));
        $topDays = array_keys(array_slice($dayPatterns, 0, 2, true));

        // Generate suggestions for the upcoming weeks
        $suggestions = [];
        $startDate = now()->startOfDay();
        $endDate = now()->addWeeks(4)->endOfDay();

        // Create suggestions based on patterns
        foreach ($topTimes as $time) {
            foreach ($topDays as $dayOfWeek) {
                if (count($suggestions) >= $limit) {
                    break 2;
                }

                // Find next occurrence of this day of week
                $nextDate = $startDate->copy();
                while ($nextDate->format('N') != $dayOfWeek || $nextDate->isPast()) {
                    $nextDate->addDay();
                }

                // Skip if too far in the future
                if ($nextDate->gt($endDate)) {
                    continue;
                }

                $suggestedDateTime = $nextDate->copy()->setTimeFromTimeString($time);
                
                // Skip if in the past or too soon (less than 24 hours)
                if ($suggestedDateTime->isBefore(now()->addDay())) {
                    continue;
                }

                $suggestions[] = [
                    'datetime' => $suggestedDateTime,
                    'label' => $this->formatSuggestionLabel($suggestedDateTime),
                    'frequency' => $timePatterns[$time] ?? 0,
                    'is_pattern' => true,
                    'iso_string' => $suggestedDateTime->toISOString(),
                    'value' => $suggestedDateTime->format('Y-m-d\TH:i'),
                ];
            }
        }

        // Fill remaining slots with default suggestions if needed
        while (count($suggestions) < $limit) {
            $defaultSuggestions = $this->getDefaultSuggestedTimes($limit - count($suggestions));
            foreach ($defaultSuggestions as $default) {
                if (count($suggestions) >= $limit) {
                    break;
                }
                
                // Avoid duplicates
                $exists = false;
                foreach ($suggestions as $existing) {
                    if ($existing['datetime']->format('Y-m-d H:i') === $default['datetime']->format('Y-m-d H:i')) {
                        $exists = true;
                        break;
                    }
                }
                
                if (!$exists) {
                    $suggestions[] = $default;
                }
            }
            break;
        }

        // Sort suggestions by date
        usort($suggestions, function ($a, $b) {
            return $a['datetime']->compare($b['datetime']);
        });

        return array_slice($suggestions, 0, $limit);
    }

    /**
     * Get default suggested times when no historical data is available
     */
    private function getDefaultSuggestedTimes(int $limit = 4): array
    {
        $suggestions = [];
        $commonTimes = ['10:00', '14:00', '16:00', '18:00'];
        $commonDays = [2, 4]; // Tuesday, Thursday
        
        $startDate = now()->startOfDay();
        
        foreach ($commonTimes as $time) {
            foreach ($commonDays as $dayOfWeek) {
                // Find next occurrence of this day of week
                $nextDate = $startDate->copy();
                while ($nextDate->format('N') != $dayOfWeek || $nextDate->isPast()) {
                    $nextDate->addDay();
                }
                
                $suggestedDateTime = $nextDate->copy()->setTimeFromTimeString($time);
                
                // Skip if too soon
                if ($suggestedDateTime->isBefore(now()->addDay())) {
                    continue;
                }
                
                $suggestions[] = [
                    'datetime' => $suggestedDateTime,
                    'label' => $this->formatSuggestionLabel($suggestedDateTime),
                    'frequency' => 0,
                    'is_pattern' => false,
                    'iso_string' => $suggestedDateTime->toISOString(),
                    'value' => $suggestedDateTime->format('Y-m-d\TH:i'),
                ];
                
                if (count($suggestions) >= $limit) {
                    break 2;
                }
            }
        }
        
        return $suggestions;
    }

    /**
     * Format suggestion label for display
     */
    private function formatSuggestionLabel(Carbon $datetime): string
    {
        // Lithuanian day names
        $dayNames = [
            1 => 'Pirmadienis',
            2 => 'Antradienis', 
            3 => 'Trečiadienis',
            4 => 'Ketvirtadienis',
            5 => 'Penktadienis',
            6 => 'Šeštadienis',
            7 => 'Sekmadienis'
        ];
        
        $dayName = $dayNames[$datetime->format('N')];
        $dateStr = $datetime->format('m-d'); // Month-day
        $timeStr = $datetime->format('H:i');
        
        // Check if it's this week or next week
        $weekDiff = $datetime->weekOfYear - now()->weekOfYear;
        $yearDiff = $datetime->year - now()->year;
        
        if ($yearDiff === 0 && $weekDiff === 0) {
            return "Šią savaitę, {$dayName} {$timeStr}";
        } elseif ($yearDiff === 0 && $weekDiff === 1) {
            return "Kitą savaitę, {$dayName} {$timeStr}";
        } else {
            return "{$dayName}, {$dateStr} {$timeStr}";
        }
    }

    /**
     * Get most common meeting times for an institution
     */
    public function getMostCommonTimes(Institution $institution, int $limit = 5): Collection
    {
        return $institution->meetings()
            ->where('start_time', '>=', now()->subMonths(12))
            ->get(['start_time'])
            ->groupBy(function ($meeting) {
                return $meeting->start_time->format('H:i');
            })
            ->map(function ($meetings) {
                return $meetings->count();
            })
            ->sortDesc()
            ->take($limit)
            ->keys();
    }

    /**
     * Get most common meeting days for an institution
     */
    public function getMostCommonDays(Institution $institution): Collection
    {
        $dayNames = [
            1 => 'Pirmadienis',
            2 => 'Antradienis', 
            3 => 'Trečiadienis',
            4 => 'Ketvirtadienis',
            5 => 'Penktadienis',
            6 => 'Šeštadienis',
            7 => 'Sekmadienis'
        ];

        return $institution->meetings()
            ->where('start_time', '>=', now()->subMonths(12))
            ->get(['start_time'])
            ->groupBy(function ($meeting) {
                return $meeting->start_time->format('N');
            })
            ->map(function ($meetings, $dayNumber) use ($dayNames) {
                return [
                    'day_number' => $dayNumber,
                    'day_name' => $dayNames[$dayNumber],
                    'count' => $meetings->count()
                ];
            })
            ->sortByDesc('count')
            ->values();
    }
}