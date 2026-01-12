<template>
  <div class="event-timeline-vertical">
    <!-- Header -->
    <div class="flex items-center justify-between mb-8">
      <h3 class="text-lg font-semibold text-zinc-800 dark:text-zinc-100 flex items-center gap-2">
        <div class="w-1 h-5 bg-vusa-red rounded-full" />
        {{ $t('Renginių kalendorius') }}
      </h3>
      
      <!-- Date range indicator -->
      <div class="text-xs text-zinc-400 dark:text-zinc-500">
        {{ formatDateCompact(dateRange.start) }} — {{ formatDateCompact(dateRange.end) }}
      </div>
    </div>

    <!-- Load past button -->
    <button
      v-if="canLoadPast"
      type="button"
      class="w-full flex items-center justify-center gap-2 py-2.5 mb-6 text-sm font-medium text-zinc-500 dark:text-zinc-400 bg-zinc-50 dark:bg-zinc-800/50 rounded-lg border border-zinc-200 dark:border-zinc-700/50 hover:bg-zinc-100 dark:hover:bg-zinc-800 hover:text-vusa-red transition-colors"
      :disabled="loadingPast"
      @click="loadMorePast"
    >
      <ArrowUp v-if="!loadingPast" class="w-4 h-4" />
      <RefreshCw v-else class="w-4 h-4 animate-spin" />
      {{ loadingPast ? $t('Kraunama...') : $t('Rodyti ankstesnius') }}
    </button>

    <!-- Timeline container -->
    <div class="relative">
      <!-- Vertical timeline line -->
      <div class="absolute left-5 top-0 bottom-0 w-px bg-zinc-200 dark:bg-zinc-700" />

      <!-- Event entries with integrated today marker -->
      <div class="space-y-2">
        <template v-for="(group, groupIndex) in eventGroups" :key="group.dateKey">
          <!-- Today marker - show BEFORE the first non-past group -->
          <div 
            v-if="shouldShowTodayMarkerBefore(groupIndex)"
            class="relative z-10 flex items-center gap-3 py-6"
          >
            <!-- Today dot -->
            <div class="relative flex items-center justify-center w-10">
              <div class="absolute w-5 h-5 bg-vusa-red/20 rounded-full animate-ping" />
              <div class="w-3 h-3 bg-vusa-red rounded-full" />
            </div>
            
            <!-- Today label -->
            <div class="flex-1 h-px bg-vusa-red/20" />
            <span class="px-3 py-1 text-xs font-semibold text-white bg-vusa-red rounded-full">
              {{ $t('Šiandien') }}
            </span>
            <div class="flex-1 h-px bg-vusa-red/20" />
          </div>

          <!-- Date separator for new dates -->
          <div 
            v-if="groupIndex === 0 || !isSameDateGroup(group, eventGroups[groupIndex - 1])"
            class="flex items-center gap-3 pt-6 pb-3"
          >
            <!-- Date indicator on timeline -->
            <div 
              class="relative flex items-center justify-center w-10"
              :class="group.isToday ? 'z-20' : 'z-10'"
            >
              <div 
                class="w-2.5 h-2.5 rounded-full"
                :class="group.isPast 
                  ? 'bg-zinc-300 dark:bg-zinc-600' 
                  : group.isToday 
                    ? 'bg-vusa-red' 
                    : 'bg-vusa-red/70'"
              />
            </div>
            
            <!-- Date label -->
            <div 
              class="text-sm font-medium"
              :class="group.isPast 
                ? 'text-zinc-400 dark:text-zinc-500' 
                : 'text-zinc-700 dark:text-zinc-300'"
            >
              {{ formatDateFull(group.date) }}
            </div>
          </div>

          <!-- Events for this date -->
          <a
            v-for="event in group.events"
            :key="event.id"
            :href="route('calendar.event', { calendar: event.id, lang: locale })"
            class="group relative flex items-center gap-4 ml-10 py-3 px-4 rounded-xl border transition-all duration-200"
            :class="[
              group.isPast 
                ? 'bg-zinc-50/50 dark:bg-zinc-800/30 border-zinc-100 dark:border-zinc-800 opacity-70 hover:opacity-100' 
                : 'bg-white dark:bg-zinc-800/80 border-zinc-200 dark:border-zinc-700 hover:border-vusa-red/40 hover:shadow-sm',
              group.isNextUpcoming && event === group.events[0] ? 'ring-1 ring-vusa-red/20' : ''
            ]"
          >
            <!-- Upcoming badge -->
            <div 
              v-if="group.isNextUpcoming && event === group.events[0]"
              class="absolute -top-2 right-3 px-2 py-0.5 bg-vusa-red text-white text-[10px] font-semibold rounded-full"
            >
              {{ $t('Artėja') }}
            </div>

            <!-- Event thumbnail -->
            <div class="flex-shrink-0 w-11 h-11 rounded-lg overflow-hidden bg-zinc-100 dark:bg-zinc-700">
              <img 
                v-if="getEventImage(event)"
                :src="getEventImage(event)!"
                :alt="getEventTitle(event)"
                class="w-full h-full object-cover"
                loading="lazy"
              >
              <div 
                v-else 
                class="w-full h-full bg-gradient-to-br from-vusa-red/10 to-vusa-red/20 flex items-center justify-center"
              >
                <Calendar class="w-5 h-5 text-vusa-red/50" />
              </div>
            </div>

            <!-- Event content -->
            <div class="flex-1 min-w-0">
              <h4 
                class="font-medium text-sm line-clamp-2 leading-snug transition-colors group-hover:text-vusa-red"
                :class="group.isPast ? 'text-zinc-500 dark:text-zinc-400' : 'text-zinc-800 dark:text-zinc-100'"
              >
                {{ getEventTitle(event) }}
              </h4>
              
              <div class="flex items-center gap-2 mt-1 text-xs" :class="group.isPast ? 'text-zinc-400 dark:text-zinc-500' : 'text-zinc-500 dark:text-zinc-400'">
                <Clock class="w-3 h-3" />
                <span>{{ formatEventTime(event.date) }}</span>
                <template v-if="getEventLocation(event)">
                  <span class="text-zinc-300 dark:text-zinc-600">·</span>
                  <span class="truncate">{{ getEventLocation(event) }}</span>
                </template>
              </div>
            </div>

            <!-- Arrow indicator -->
            <ChevronRight class="flex-shrink-0 w-4 h-4 text-zinc-300 dark:text-zinc-600 group-hover:text-vusa-red transition-colors" />
          </a>
        </template>

        <!-- Today marker at the end (when all events are in the past) -->
        <div 
          v-if="shouldShowTodayMarkerAtEnd"
          class="relative z-10 flex items-center gap-3 py-6"
        >
          <!-- Today dot -->
          <div class="relative flex items-center justify-center w-10">
            <div class="absolute w-5 h-5 bg-vusa-red/20 rounded-full animate-ping" />
            <div class="w-3 h-3 bg-vusa-red rounded-full" />
          </div>
          
          <!-- Today label -->
          <div class="flex-1 h-px bg-vusa-red/20" />
          <span class="px-3 py-1 text-xs font-semibold text-white bg-vusa-red rounded-full">
            {{ $t('Šiandien') }}
          </span>
          <div class="flex-1 h-px bg-vusa-red/20" />
        </div>
      </div>

      <!-- Empty state -->
      <div 
        v-if="eventGroups.length === 0"
        class="flex flex-col items-center justify-center py-16 text-center"
      >
        <div class="w-14 h-14 rounded-full bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center mb-4">
          <CalendarX class="w-6 h-6 text-zinc-400 dark:text-zinc-500" />
        </div>
        <p class="text-zinc-500 dark:text-zinc-400 text-sm">{{ $t('Nėra renginių') }}</p>
        <p class="text-xs text-zinc-400 dark:text-zinc-500 mt-1">{{ $t('Šiuo laikotarpiu renginių nerasta') }}</p>
      </div>
    </div>

    <!-- Load future button -->
    <button
      v-if="canLoadFuture"
      type="button"
      class="w-full flex items-center justify-center gap-2 py-2.5 mt-6 text-sm font-medium text-zinc-500 dark:text-zinc-400 bg-zinc-50 dark:bg-zinc-800/50 rounded-lg border border-zinc-200 dark:border-zinc-700/50 hover:bg-zinc-100 dark:hover:bg-zinc-800 hover:text-vusa-red transition-colors"
      :disabled="loadingFuture"
      @click="loadMoreFuture"
    >
      <ArrowDown v-if="!loadingFuture" class="w-4 h-4" />
      <RefreshCw v-else class="w-4 h-4 animate-spin" />
      {{ loadingFuture ? $t('Kraunama...') : $t('Rodyti vėlesnius') }}
    </button>

    <!-- Action buttons -->
    <div class="flex flex-col gap-3 mt-8 pt-6 border-t border-zinc-100 dark:border-zinc-800">
      <Button as="a" :href="route('calendar.list', { lang: locale })" class="w-full" size="sm">
        <Calendar class="w-4 h-4 mr-2" />
        {{ $t('Visi renginiai') }}
      </Button>
      <Button variant="ghost" size="sm" class="w-full text-zinc-500" @click="$emit('openSyncModal')">
        <RefreshCw class="w-4 h-4 mr-2" />
        {{ $t('Sinchronizuoti kalendorių') }}
      </Button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { trans as $t } from "laravel-vue-i18n";
import { computed, ref } from "vue";
import { format, addDays, subDays, startOfDay, isAfter, isBefore, isSameDay, parseISO } from "date-fns";
import { lt, enUS } from "date-fns/locale";
import { 
  ArrowUp, 
  ArrowDown, 
  RefreshCw, 
  Calendar, 
  Clock, 
  MapPin, 
  ChevronRight,
  CalendarX
} from "lucide-vue-next";

import { Button } from "@/Components/ui/button";

const props = defineProps<{
  events: App.Entities.Calendar[];
  locale: string;
}>();

const emit = defineEmits<{
  (e: 'openSyncModal'): void;
}>();

// Configuration
const INITIAL_DAYS_PAST = 7;
const INITIAL_DAYS_FUTURE = 21;
const LOAD_MORE_DAYS = 14;

// State
const daysPast = ref(INITIAL_DAYS_PAST);
const daysFuture = ref(INITIAL_DAYS_FUTURE);
const loadingPast = ref(false);
const loadingFuture = ref(false);

// Computed values
const dateLocale = computed(() => props.locale === 'lt' ? lt : enUS);
const today = computed(() => startOfDay(new Date()));

const dateRange = computed(() => {
  const start = subDays(today.value, daysPast.value);
  const end = addDays(today.value, daysFuture.value);
  return { start, end };
});

const canLoadPast = computed(() => daysPast.value < 90);
const canLoadFuture = computed(() => daysFuture.value < 90);

// Find upcoming events to determine "next upcoming"
const nextUpcomingDate = computed(() => {
  const upcomingEvents = props.events
    .filter(e => !isBefore(new Date(e.date), today.value))
    .sort((a, b) => new Date(a.date).getTime() - new Date(b.date).getTime());
  
  return upcomingEvents[0] ? startOfDay(new Date(upcomingEvents[0].date)) : null;
});

// Group events by date
const eventGroups = computed(() => {
  const groups: Array<{
    dateKey: string;
    date: Date;
    events: App.Entities.Calendar[];
    isPast: boolean;
    isToday: boolean;
    isNextUpcoming: boolean;
  }> = [];

  // Filter events within date range
  const rangeEvents = props.events.filter(event => {
    const eventDate = startOfDay(new Date(event.date));
    return !isBefore(eventDate, dateRange.value.start) && !isAfter(eventDate, dateRange.value.end);
  });

  // Group by date
  const groupMap = new Map<string, App.Entities.Calendar[]>();
  
  rangeEvents.forEach(event => {
    const eventDate = startOfDay(new Date(event.date));
    const dateKey = format(eventDate, 'yyyy-MM-dd');
    
    if (!groupMap.has(dateKey)) {
      groupMap.set(dateKey, []);
    }
    groupMap.get(dateKey)!.push(event);
  });

  // Convert to array and add metadata
  groupMap.forEach((events, dateKey) => {
    const date = parseISO(dateKey);
    const isPast = isBefore(date, today.value);
    const isToday = isSameDay(date, today.value);
    const isNextUpcoming = nextUpcomingDate.value ? isSameDay(date, nextUpcomingDate.value) : false;

    // Sort events by time
    events.sort((a, b) => new Date(a.date).getTime() - new Date(b.date).getTime());

    groups.push({
      dateKey,
      date,
      events,
      isPast,
      isToday,
      isNextUpcoming,
    });
  });

  // Sort groups by date
  groups.sort((a, b) => a.date.getTime() - b.date.getTime());

  return groups;
});

// Track if today marker has been shown
const todayMarkerShownRef = ref(false);

// Determine if today marker should be shown before a specific group index
const shouldShowTodayMarkerBefore = (groupIndex: number): boolean => {
  const groups = eventGroups.value;
  if (groups.length === 0) return false;
  
  const currentGroup = groups[groupIndex];
  if (!currentGroup) return false;
  
  // Check if this is the first non-past group
  const isFirstNonPast = !currentGroup.isPast;
  
  // Check if all previous groups are past
  const allPreviousArePast = groups.slice(0, groupIndex).every(g => g.isPast);
  
  // Check if there's at least one past group before (otherwise marker would be at very top)
  const hasPastGroupsBefore = groupIndex > 0 && groups.slice(0, groupIndex).some(g => g.isPast);
  
  // Show today marker if this is the transition point from past to non-past events
  // OR if all events are in the past and this is the last group
  if (isFirstNonPast && allPreviousArePast && hasPastGroupsBefore) {
    return true;
  }
  
  return false;
};

// Check if today marker should be shown at the end (all events are past)
const shouldShowTodayMarkerAtEnd = computed(() => {
  const groups = eventGroups.value;
  if (groups.length === 0) return false;
  
  // Show at end only if ALL events are in the past
  return groups.every(g => g.isPast);
});

// Helper functions
const getEventImage = (event: App.Entities.Calendar): string | null => {
  // Use main_image_url accessor which handles fallback to first gallery image
  return (event as any).main_image_url || null;
};

const getEventTitle = (event: App.Entities.Calendar): string => {
  if (Array.isArray(event.title)) {
    const translations = event.title as Array<{ locale?: string; value?: string }>;
    const translation = translations.find(t => t?.locale === props.locale);
    return translation?.value || translations[0]?.value || '';
  }
  return String(event.title || '');
};

const getEventLocation = (event: App.Entities.Calendar): string | null => {
  if (!event.location) return null;
  if (Array.isArray(event.location)) {
    const translations = event.location as Array<{ locale?: string; value?: string }>;
    const translation = translations.find(t => t?.locale === props.locale);
    return translation?.value || translations[0]?.value || null;
  }
  return String(event.location);
};

const isSameDateGroup = (group1: { dateKey: string }, group2: { dateKey: string } | undefined): boolean => {
  return group2 ? group1.dateKey === group2.dateKey : false;
};

// Date formatting
const formatDateCompact = (date: Date): string => {
  return format(date, 'MMM d', { locale: dateLocale.value });
};

const formatDateFull = (date: Date): string => {
  return format(date, 'EEEE, d MMMM', { locale: dateLocale.value });
};

const formatEventTime = (dateStr: string): string => {
  try {
    const date = parseISO(dateStr);
    return format(date, 'HH:mm');
  } catch {
    return '';
  }
};

// Load more actions
const loadMorePast = () => {
  loadingPast.value = true;
  setTimeout(() => {
    daysPast.value += LOAD_MORE_DAYS;
    loadingPast.value = false;
  }, 300);
};

const loadMoreFuture = () => {
  loadingFuture.value = true;
  setTimeout(() => {
    daysFuture.value += LOAD_MORE_DAYS;
    loadingFuture.value = false;
  }, 300);
};
</script>

<style scoped>
.event-timeline-vertical {
  width: 100%;
}
</style>
