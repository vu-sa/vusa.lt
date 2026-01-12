<template>
  <div class="event-timeline-container overflow-hidden px-4">
    <!-- Header with navigation and date range -->
    <div class="flex items-center justify-between mb-3 px-2">
      <!-- Left navigation -->
      <button
        type="button"
        class="flex items-center justify-center w-9 h-9 rounded-full bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 shadow-sm hover:bg-zinc-50 dark:hover:bg-zinc-700 hover:border-vusa-red dark:hover:border-vusa-red transition-all duration-200 group"
        :aria-label="$t('Ankstesni renginiai')"
        @click="navigatePast"
      >
        <ChevronLeft class="w-4 h-4 text-zinc-600 dark:text-zinc-400 group-hover:text-vusa-red transition-colors" />
      </button>

      <!-- Date range indicator -->
      <div class="flex items-center gap-2 text-xs sm:text-sm">
        <span class="font-medium text-zinc-500 dark:text-zinc-400">
          {{ formatDateLabel(dateRange.start) }}
        </span>
        <div class="flex items-center gap-1">
          <div class="w-6 h-px bg-zinc-300 dark:bg-zinc-600" />
          <div class="w-1.5 h-1.5 rounded-full bg-vusa-red" />
          <div class="w-6 h-px bg-zinc-300 dark:bg-zinc-600" />
        </div>
        <span class="font-medium text-zinc-500 dark:text-zinc-400">
          {{ formatDateLabel(dateRange.end) }}
        </span>
      </div>

      <!-- Right navigation -->
      <button
        type="button"
        class="flex items-center justify-center w-9 h-9 rounded-full bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 shadow-sm hover:bg-zinc-50 dark:hover:bg-zinc-700 hover:border-vusa-red dark:hover:border-vusa-red transition-all duration-200 group"
        :aria-label="$t('Vėlesni renginiai')"
        @click="navigateFuture"
      >
        <ChevronRight class="w-4 h-4 text-zinc-600 dark:text-zinc-400 group-hover:text-vusa-red transition-colors" />
      </button>
    </div>

    <!-- Timeline container with proper overflow handling -->
    <div class="relative rounded-xl bg-white dark:bg-zinc-800/90 border border-zinc-200 dark:border-zinc-700 p-4 overflow-hidden">
      <!-- Decorative background pattern -->
      <div class="absolute inset-0 opacity-[0.015] dark:opacity-[0.025]" aria-hidden="true">
        <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,%3Csvg width=&quot;60&quot; height=&quot;60&quot; viewBox=&quot;0 0 60 60&quot; xmlns=&quot;http://www.w3.org/2000/svg&quot;%3E%3Cg fill=&quot;none&quot; fill-rule=&quot;evenodd&quot;%3E%3Cg fill=&quot;%23bd1b21&quot; fill-opacity=&quot;1&quot;%3E%3Cpath d=&quot;M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z&quot;/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');"></div>
      </div>

      <!-- Today line (vertical) -->
      <div 
        class="absolute top-0 bottom-0 w-0.5 z-10 pointer-events-none"
        :style="{ left: `${todayPosition}%` }"
      >
        <div class="absolute inset-0 w-1 -ml-0.5 bg-vusa-red/20 blur-sm" />
        <div class="absolute inset-0 bg-gradient-to-b from-vusa-red via-vusa-red to-vusa-red/30 rounded-full" />
        <!-- Today badge at top -->
        <div class="absolute -top-1 left-1/2 -translate-x-1/2 -translate-y-full mb-1 px-2 py-0.5 bg-vusa-red text-white text-[9px] font-bold uppercase tracking-wide rounded shadow-lg whitespace-nowrap">
          {{ $t('Šiandien') }}
        </div>
      </div>

      <!-- Timeline track with events -->
      <div class="relative h-36 sm:h-40">
        <!-- Horizontal axis line -->
        <div class="absolute left-0 right-0 bottom-6 h-px bg-gradient-to-r from-transparent via-zinc-300 dark:via-zinc-600 to-transparent" />

        <!-- Date tick marks -->
        <div class="absolute left-0 right-0 bottom-0 h-6 flex">
          <div 
            v-for="marker in dateMarkers" 
            :key="marker.date.toISOString()"
            class="absolute flex flex-col items-center"
            :style="{ left: `${marker.position}%`, transform: 'translateX(-50%)' }"
          >
            <div 
              class="w-px mb-0.5"
              :class="[
                marker.isWeekStart ? 'h-2 bg-zinc-400 dark:bg-zinc-500' : 'h-1.5 bg-zinc-300 dark:bg-zinc-600'
              ]"
            />
            <span 
              v-if="marker.showLabel"
              class="text-[9px] font-medium text-zinc-400 dark:text-zinc-500 whitespace-nowrap"
            >
              {{ marker.label }}
            </span>
          </div>
        </div>

        <!-- Events positioned on timeline -->
        <div class="absolute inset-x-0 top-2 bottom-8">
          <div 
            v-for="group in eventGroups" 
            :key="group.dateKey"
            class="absolute flex flex-col items-center"
            :style="{ 
              left: `${group.position}%`, 
              transform: 'translateX(-50%)',
            }"
          >
            <!-- Vertical connector to axis -->
            <div class="absolute top-full w-px h-2 bg-zinc-300 dark:bg-zinc-600" />
            
            <!-- Stacked event thumbnails -->
            <div class="flex flex-col items-center gap-1">
              <HoverCard v-for="(event, eventIndex) in group.visibleEvents" :key="event.id" :open-delay="150" :close-delay="100">
                <HoverCardTrigger as-child>
                  <button 
                    type="button"
                    class="event-thumbnail relative transition-all duration-200"
                    :class="[
                      group.isNextUpcoming && eventIndex === 0 
                        ? 'ring-2 ring-vusa-red ring-offset-2 dark:ring-offset-zinc-800 scale-105 z-20' 
                        : 'hover:scale-105 hover:z-20',
                      !isUpcoming(event) ? 'opacity-75 grayscale-[15%] hover:opacity-100 hover:grayscale-0' : ''
                    ]"
                    @click="navigateToEvent(event)"
                  >
                    <div class="w-11 h-11 sm:w-12 sm:h-12 rounded-lg overflow-hidden shadow-lg border-2 border-white dark:border-zinc-700 bg-white dark:bg-zinc-700">
                      <img 
                        v-if="getEventImage(event)"
                        :src="getEventImage(event)!"
                        :alt="getEventTitle(event)"
                        class="w-full h-full object-cover"
                        loading="lazy"
                      >
                      <div 
                        v-else 
                        class="w-full h-full flex items-center justify-center"
                        :class="getCategoryColorClass(event)"
                      >
                        <component :is="getCategoryIcon(event)" class="w-5 h-5 text-white/90" />
                      </div>
                    </div>
                    
                    <!-- Pulsing glow for next upcoming -->
                    <div 
                      v-if="group.isNextUpcoming && eventIndex === 0"
                      class="absolute -inset-1 rounded-xl bg-vusa-red/30 blur-md -z-10 animate-pulse"
                    />
                  </button>
                </HoverCardTrigger>

                <HoverCardContent 
                  class="w-72 p-0 overflow-hidden z-50"
                  :side="group.position > 75 ? 'left' : group.position < 25 ? 'right' : 'top'"
                  :side-offset="8"
                >
                  <EventHoverCard :event="event" :locale="locale" />
                </HoverCardContent>
              </HoverCard>

              <!-- Overflow badge -->
              <button
                v-if="group.overflowCount > 0"
                type="button"
                class="flex items-center justify-center w-6 h-6 rounded-md bg-zinc-200 dark:bg-zinc-700 text-[10px] font-bold text-zinc-600 dark:text-zinc-400 hover:bg-vusa-red hover:text-white transition-all duration-200"
                :title="`${group.overflowCount} ${$t('daugiau renginių')}`"
                @click="navigateToDateEvents(group.date)"
              >
                +{{ group.overflowCount }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Featured upcoming events cards -->
    <div 
      v-if="nextUpcomingEvents.length > 0"
      class="mt-6 grid gap-3"
      :class="nextUpcomingEvents.length === 1 ? 'grid-cols-1 max-w-md mx-auto' : 'grid-cols-1 sm:grid-cols-2'"
    >
      <a
        v-for="(event, index) in nextUpcomingEvents"
        :key="event.id"
        :href="route('calendar.event', { calendar: event.id, lang: locale })"
        class="group relative flex items-center gap-4 p-4 rounded-xl bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 shadow-sm hover:shadow-md hover:border-vusa-red/50 dark:hover:border-vusa-red/50 transition-all duration-300"
        :class="index === 0 ? 'ring-2 ring-vusa-red/20 dark:ring-vusa-red/30' : ''"
      >
        <!-- Priority indicator -->
        <div 
          v-if="index === 0"
          class="absolute -top-2 -right-2 px-2 py-0.5 bg-vusa-red text-white text-[10px] font-bold rounded-full shadow-md"
        >
          {{ $t('Artėja') }}
        </div>

        <!-- Event image -->
        <div class="flex-shrink-0 w-16 h-16 rounded-lg overflow-hidden shadow-sm">
          <img 
            v-if="getEventImage(event)"
            :src="getEventImage(event)!"
            :alt="getEventTitle(event)"
            class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105"
          >
          <div 
            v-else 
            class="w-full h-full flex items-center justify-center"
            :class="getCategoryColorClass(event)"
          >
            <component :is="getCategoryIcon(event)" class="w-7 h-7 text-white/90" />
          </div>
        </div>

        <!-- Event details -->
        <div class="flex-1 min-w-0">
          <h4 class="font-semibold text-zinc-900 dark:text-zinc-100 line-clamp-1 group-hover:text-vusa-red transition-colors">
            {{ getEventTitle(event) }}
          </h4>
          <div class="flex items-center gap-2 mt-1 text-sm text-zinc-500 dark:text-zinc-400">
            <Calendar class="w-4 h-4 flex-shrink-0" />
            <span>{{ formatEventDate(event.date) }}</span>
          </div>
          <div v-if="getEventLocation(event)" class="flex items-center gap-2 mt-0.5 text-sm text-zinc-500 dark:text-zinc-400">
            <MapPin class="w-4 h-4 flex-shrink-0" />
            <span class="truncate">{{ getEventLocation(event) }}</span>
          </div>
        </div>

        <!-- Arrow -->
        <ChevronRight class="w-5 h-5 text-zinc-400 group-hover:text-vusa-red transition-colors flex-shrink-0" />
      </a>
    </div>

    <!-- Action buttons -->
    <div class="flex flex-wrap justify-center gap-3 mt-6">
      <Button as="a" :href="route('calendar.list', { lang: locale })">
        <Calendar class="w-4 h-4 mr-2" />
        {{ $t('Visi renginiai') }}
      </Button>
      <Button variant="outline" @click="$emit('openSyncModal')">
        <RefreshCw class="w-4 h-4 mr-2" />
        {{ $t('Sinchronizuoti kalendorių') }}
      </Button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { trans as $t } from "laravel-vue-i18n";
import { computed, ref, onMounted, type Component } from "vue";
import { format, addDays, subDays, startOfDay, isAfter, isBefore, isSameDay, startOfWeek, differenceInDays } from "date-fns";
import { lt, enUS } from "date-fns/locale";
import { 
  Calendar, 
  GraduationCap, 
  Users, 
  Trophy, 
  Image as ImageIcon, 
  Dumbbell, 
  Music, 
  ChevronLeft, 
  ChevronRight, 
  MapPin,
  RefreshCw,
  Plus
} from "lucide-vue-next";

import { Button } from "@/Components/ui/button";
import { HoverCard, HoverCardContent, HoverCardTrigger } from "@/Components/ui/hover-card";
import EventHoverCard from "./EventHoverCard.vue";

const props = defineProps<{
  events: App.Entities.Calendar[];
  locale: string;
}>();

const emit = defineEmits<{
  (e: 'openSyncModal'): void;
}>();

// Configuration
const DAYS_PAST = 7;
const DAYS_FUTURE = 21;
const TOTAL_DAYS = DAYS_PAST + DAYS_FUTURE;
const MAX_VISIBLE_EVENTS_PER_DAY = 2;

// State
const timelineRef = ref<HTMLElement | null>(null);
const offsetWeeks = ref(0); // Navigation offset in weeks

// Computed values
const dateLocale = computed(() => props.locale === 'lt' ? lt : enUS);

const today = computed(() => startOfDay(new Date()));

const dateRange = computed(() => {
  const baseStart = subDays(today.value, DAYS_PAST);
  const start = addDays(baseStart, offsetWeeks.value * 7);
  const end = addDays(start, TOTAL_DAYS);
  return { start, end };
});

const todayPosition = computed(() => {
  const daysFromStart = differenceInDays(today.value, dateRange.value.start);
  return Math.max(2, Math.min(98, (daysFromStart / TOTAL_DAYS) * 100)); // Clamp to avoid edge overflow
});

// Date markers for x-axis
const dateMarkers = computed(() => {
  const markers: Array<{
    date: Date;
    position: number;
    label: string;
    isWeekStart: boolean;
    showLabel: boolean;
  }> = [];

  for (let i = 0; i <= TOTAL_DAYS; i += 1) {
    const date = addDays(dateRange.value.start, i);
    const isWeekStart = date.getDay() === 1; // Monday
    // Show label only for week starts, but not too close to edges
    const position = (i / TOTAL_DAYS) * 100;
    const showLabel = isWeekStart && position > 5 && position < 95;

    markers.push({
      date,
      position,
      label: format(date, 'd MMM', { locale: dateLocale.value }),
      isWeekStart,
      showLabel,
    });
  }

  // Only return every 2nd day tick mark to reduce clutter
  return markers.filter((_, i) => i % 2 === 0);
});

// Group events by date
const eventGroups = computed(() => {
  const groups: Map<string, {
    dateKey: string;
    date: Date;
    position: number;
    events: App.Entities.Calendar[];
    visibleEvents: App.Entities.Calendar[];
    overflowCount: number;
    isNextUpcoming: boolean;
  }> = new Map();

  // Filter events within date range
  const rangeEvents = props.events.filter(event => {
    const eventDate = startOfDay(new Date(event.date));
    return !isBefore(eventDate, dateRange.value.start) && !isAfter(eventDate, dateRange.value.end);
  });

  // Find the next upcoming event date
  const upcomingEvents = rangeEvents
    .filter(e => !isBefore(new Date(e.date), today.value))
    .sort((a, b) => new Date(a.date).getTime() - new Date(b.date).getTime());
  const nextUpcomingDate = upcomingEvents[0] ? startOfDay(new Date(upcomingEvents[0].date)) : null;

  rangeEvents.forEach(event => {
    const eventDate = startOfDay(new Date(event.date));
    const dateKey = format(eventDate, 'yyyy-MM-dd');

    if (!groups.has(dateKey)) {
      const daysFromStart = differenceInDays(eventDate, dateRange.value.start);
      const position = (daysFromStart / TOTAL_DAYS) * 100;

      groups.set(dateKey, {
        dateKey,
        date: eventDate,
        position,
        events: [],
        visibleEvents: [],
        overflowCount: 0,
        isNextUpcoming: nextUpcomingDate ? isSameDay(eventDate, nextUpcomingDate) : false,
      });
    }

    groups.get(dateKey)!.events.push(event);
  });

  // Process visible events and overflow
  groups.forEach(group => {
    // Sort by date, then prioritize events with images
    group.events.sort((a, b) => {
      const aHasImage = getEventImage(a) ? 1 : 0;
      const bHasImage = getEventImage(b) ? 1 : 0;
      if (aHasImage !== bHasImage) return bHasImage - aHasImage;
      return new Date(a.date).getTime() - new Date(b.date).getTime();
    });

    group.visibleEvents = group.events.slice(0, MAX_VISIBLE_EVENTS_PER_DAY);
    group.overflowCount = Math.max(0, group.events.length - MAX_VISIBLE_EVENTS_PER_DAY);
  });

  return Array.from(groups.values()).sort((a, b) => a.date.getTime() - b.date.getTime());
});

// Next 2 upcoming events for featured cards
const nextUpcomingEvents = computed(() => {
  return props.events
    .filter(e => !isBefore(new Date(e.date), today.value))
    .sort((a, b) => new Date(a.date).getTime() - new Date(b.date).getTime())
    .slice(0, 2);
});

// Count of all future events (for indicator)
const futureEventsCount = computed(() => {
  return props.events.filter(e => !isBefore(new Date(e.date), today.value)).length;
});

console.log('futureEventsCount', futureEventsCount.value);

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

const isUpcoming = (event: App.Entities.Calendar): boolean => {
  return !isBefore(new Date(event.date), today.value);
};

// Category-based styling helpers
const getCategoryColorClass = (event: App.Entities.Calendar): string => {
  const categoryAlias = (event.category as any)?.alias || '';
  const colorMap: Record<string, string> = {
    'renginiai': 'bg-gradient-to-br from-vusa-red to-rose-600',
    'paskaitos': 'bg-gradient-to-br from-blue-500 to-indigo-600',
    'seminarai': 'bg-gradient-to-br from-purple-500 to-violet-600',
    'konkursai': 'bg-gradient-to-br from-amber-500 to-orange-600',
    'parodos': 'bg-gradient-to-br from-emerald-500 to-teal-600',
    'sportas': 'bg-gradient-to-br from-cyan-500 to-blue-600',
    'muzika': 'bg-gradient-to-br from-pink-500 to-rose-600',
  };
  return colorMap[categoryAlias] || 'bg-gradient-to-br from-vusa-red/80 to-vusa-red';
};

const getCategoryIcon = (event: App.Entities.Calendar) => {
  const categoryAlias = (event.category as any)?.alias || '';
  const iconMap: Record<string, any> = {
    'renginiai': Calendar,
    'paskaitos': GraduationCap,
    'seminarai': Users,
    'konkursai': Trophy,
    'parodos': ImageIcon,
    'sportas': Dumbbell,
    'muzika': Music,
  };
  return iconMap[categoryAlias] || Calendar;
};

const formatDateLabel = (date: Date): string => {
  return format(date, 'd MMMM', { locale: dateLocale.value });
};

const formatEventDate = (dateStr: string): string => {
  const date = new Date(dateStr);
  return format(date, 'EEEE, d MMMM', { locale: dateLocale.value });
};

// Navigation
const navigatePast = () => {
  offsetWeeks.value -= 1;
};

const navigateFuture = () => {
  offsetWeeks.value += 1;
};

const navigateToEvent = (event: App.Entities.Calendar) => {
  window.location.href = route('calendar.event', { calendar: event.id, lang: props.locale });
};

const navigateToDateEvents = (date: Date) => {
  // Navigate to events list filtered by date
  window.location.href = route('calendar.list', { lang: props.locale });
};

// Horizontal scroll with mouse wheel
const handleWheel = (e: WheelEvent) => {
  if (timelineRef.value && Math.abs(e.deltaX) < Math.abs(e.deltaY)) {
    timelineRef.value.scrollLeft += e.deltaY;
  }
};
</script>

<style scoped>
.event-timeline-container {
  width: 100%;
}

/* Custom scrollbar */
.scrollbar-thin {
  scrollbar-width: thin;
}

.scrollbar-thin::-webkit-scrollbar {
  height: 8px;
}

.scrollbar-thin::-webkit-scrollbar-track {
  background-color: rgb(244 244 245); /* zinc-100 */
  border-radius: 9999px;
}

:root.dark .scrollbar-thin::-webkit-scrollbar-track {
  background-color: rgb(39 39 42); /* zinc-800 */
}

.scrollbar-thin::-webkit-scrollbar-thumb {
  background-color: rgb(212 212 216); /* zinc-300 */
  border-radius: 9999px;
}

:root.dark .scrollbar-thin::-webkit-scrollbar-thumb {
  background-color: rgb(82 82 91); /* zinc-600 */
}

/* Subtle pulse animation for upcoming events */
@keyframes pulse-subtle {
  0%, 100% {
    opacity: 1;
    transform: scale(1);
  }
  50% {
    opacity: 0.9;
    transform: scale(1.02);
  }
}

.animate-pulse-subtle {
  animation: pulse-subtle 2s ease-in-out infinite;
}

/* Event thumbnail hover */
.event-thumbnail {
  transition: all 0.2s ease;
  cursor: pointer;
}
</style>
