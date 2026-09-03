<template>
  <div class="event-timeline-vertical">
    <!-- Header -->
    <div class="flex items-center justify-between mb-8">
      <h3 class="text-lg font-semibold text-foreground flex items-center gap-2">
        <div class="w-1 h-5 bg-brand-fill rounded-full" />
        {{ $t('Renginių kalendorius') }}
      </h3>

      <!-- Date range indicator -->
      <div class="text-xs text-muted-foreground">
        {{ formatDateCompact(dateRange.start) }} — {{ formatDateCompact(dateRange.end) }}
      </div>
    </div>

    <!-- Show/load past button -->
    <button
      v-if="!showPast && hasPastEvents"
      type="button"
      class="w-full flex items-center justify-center gap-2 py-2.5 mb-6 text-sm font-medium text-muted-foreground bg-secondary/50 rounded-lg border border-border/50 hover:bg-secondary hover:text-brand transition-colors"
      @click="showPast = true"
    >
      <ArrowUp class="w-4 h-4" />
      {{ $t('Rodyti ankstesnius') }}
    </button>
    <button
      v-else-if="showPast && canLoadPast"
      type="button"
      class="w-full flex items-center justify-center gap-2 py-2.5 mb-6 text-sm font-medium text-muted-foreground bg-secondary/50 rounded-lg border border-border/50 hover:bg-secondary hover:text-brand transition-colors"
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
      <div class="absolute left-5 top-0 bottom-0 w-px bg-secondary" />

      <!-- Event entries with integrated today marker -->
      <div class="space-y-2">
        <template v-for="(group, groupIndex) in displayedGroups" :key="group.dateKey">
          <!-- Today marker - show BEFORE the first non-past group -->
          <div
            v-if="shouldShowTodayMarkerBefore(groupIndex)"
            class="relative z-10 flex items-center gap-3 py-6"
          >
            <!-- Today dot -->
            <div class="relative flex items-center justify-center w-10">
              <div class="absolute w-5 h-5 bg-brand/20 rounded-full animate-ping" />
              <div class="w-3 h-3 bg-brand-fill rounded-full" />
            </div>

            <!-- Today label -->
            <div class="flex-1 h-px bg-brand/20" />
            <span class="px-3 py-1 text-xs font-semibold text-white bg-brand-fill rounded-full">
              {{ $t('Šiandien') }}
            </span>
            <div class="flex-1 h-px bg-brand/20" />
          </div>

          <!-- Date separator for new dates -->
          <div
            v-if="groupIndex === 0 || !isSameDateGroup(group, displayedGroups[groupIndex - 1])"
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
                  ? 'bg-border'
                  : group.isToday
                    ? 'bg-brand-fill'
                    : 'bg-brand-fill/70'"
              />
            </div>

            <!-- Date label -->
            <div
              class="text-sm font-medium"
              :class="group.isPast
                ? 'text-muted-foreground'
                : 'text-foreground'"
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
                ? 'bg-secondary/40 border-border opacity-70 hover:opacity-100'
                : 'bg-card/80 border-border hover:border-brand/40 hover:shadow-sm',
            ]"
          >
            <!-- Event thumbnail -->
            <div class="flex-shrink-0 w-11 h-11 overflow-hidden border border-border bg-secondary">
              <img
                v-if="getEventImage(event)"
                :src="getEventImage(event)!"
                :alt="getEventTitle(event)"
                class="w-full h-full object-cover"
                loading="lazy"
              >
              <div
                v-else
                class="w-full h-full bg-brand/10 flex items-center justify-center"
              >
                <Calendar class="w-5 h-5 text-brand/50" />
              </div>
            </div>

            <!-- Event content -->
            <div class="flex-1 min-w-0">
              <h4
                class="font-medium text-sm line-clamp-2 leading-snug transition-colors group-hover:text-brand"
                :class="group.isPast ? 'text-muted-foreground' : 'text-foreground'"
              >
                {{ getEventTitle(event) }}
              </h4>

              <div class="flex items-center gap-2 mt-1 text-xs" :class="group.isPast ? 'text-muted-foreground' : 'text-muted-foreground'">
                <Clock class="w-3 h-3" />
                <span>{{ formatEventTime(event.date) }}</span>
                <template v-if="getEventLocation(event)">
                  <span class="text-muted-foreground">·</span>
                  <span class="truncate">{{ getEventLocation(event) }}</span>
                </template>
              </div>
            </div>

            <!-- Arrow indicator -->
            <ChevronRight class="flex-shrink-0 w-4 h-4 text-muted-foreground group-hover:text-brand transition-colors" />
          </a>
        </template>

        <!-- Today marker at the end (when all events are in the past) -->
        <div
          v-if="shouldShowTodayMarkerAtEnd"
          class="relative z-10 flex items-center gap-3 py-6"
        >
          <!-- Today dot -->
          <div class="relative flex items-center justify-center w-10">
            <div class="absolute w-5 h-5 bg-brand/20 rounded-full animate-ping" />
            <div class="w-3 h-3 bg-brand-fill rounded-full" />
          </div>

          <!-- Today label -->
          <div class="flex-1 h-px bg-brand/20" />
          <span class="px-3 py-1 text-xs font-semibold text-white bg-brand-fill rounded-full">
            {{ $t('Šiandien') }}
          </span>
          <div class="flex-1 h-px bg-brand/20" />
        </div>
      </div>

      <!-- Empty state -->
      <div
        v-if="displayedGroups.length === 0"
        class="flex flex-col items-center justify-center py-16 text-center"
      >
        <div class="w-14 h-14 rounded-full bg-secondary flex items-center justify-center mb-4">
          <CalendarX class="w-6 h-6 text-muted-foreground" />
        </div>
        <p class="text-muted-foreground text-sm">
          {{ $t('Nėra renginių') }}
        </p>
        <p class="text-xs text-muted-foreground mt-1">
          {{ $t('Šiuo laikotarpiu renginių nerasta') }}
        </p>
      </div>
    </div>

    <!-- Load future button -->
    <button
      v-if="canLoadFuture"
      type="button"
      class="w-full flex items-center justify-center gap-2 py-2.5 mt-6 text-sm font-medium text-muted-foreground bg-secondary/50 rounded-lg border border-border/50 hover:bg-secondary hover:text-brand transition-colors"
      :disabled="loadingFuture"
      @click="loadMoreFuture"
    >
      <ArrowDown v-if="!loadingFuture" class="w-4 h-4" />
      <RefreshCw v-else class="w-4 h-4 animate-spin" />
      {{ loadingFuture ? $t('Kraunama...') : $t('Rodyti vėlesnius') }}
    </button>

    <!-- Action buttons -->
    <div class="flex flex-col gap-3 mt-8 pt-6 border-t border-border">
      <Button as="a" :href="route('calendar.list', { lang: locale })" class="w-full" size="sm">
        <Calendar class="w-4 h-4 mr-2" />
        {{ $t('Visi renginiai') }}
      </Button>
      <Button variant="ghost" size="sm" class="w-full text-muted-foreground" @click="$emit('openSyncModal')">
        <RefreshCw class="w-4 h-4 mr-2" />
        {{ $t('Sinchronizuoti kalendorių') }}
      </Button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { dateLocaleFor } from '@/Composables/useDateLocale';
import { trans as $t } from 'laravel-vue-i18n';
import { computed, ref } from 'vue';
import { format, addDays, subDays, startOfDay, isAfter, isBefore, isSameDay, parseISO } from 'date-fns';
import {
  ArrowUp,
  ArrowDown,
  RefreshCw,
  Calendar,
  Clock,
  MapPin,
  ChevronRight,
  CalendarX,
} from 'lucide-vue-next';

import { Button } from '@/Components/ui/button';

const props = defineProps<{
  events: App.Entities.Calendar[];
  locale: string;
  loadingPast?: boolean;
  loadingFuture?: boolean;
}>();

const emit = defineEmits<{
  (e: 'openSyncModal'): void;
  (e: 'loadPast'): void;
  (e: 'loadFuture'): void;
}>();

// Configuration
const INITIAL_DAYS_PAST = 7;
const INITIAL_DAYS_FUTURE = 21;
const LOAD_MORE_DAYS = 14;

// State
const daysPast = ref(INITIAL_DAYS_PAST);
const daysFuture = ref(INITIAL_DAYS_FUTURE);
const showPast = ref(false);

// Computed values
const dateLocale = computed(() => dateLocaleFor(props.locale));
const today = computed(() => startOfDay(new Date()));

const dateRange = computed(() => {
  const start = subDays(today.value, daysPast.value);
  const end = addDays(today.value, daysFuture.value);
  return { start, end };
});

const canLoadPast = computed(() => daysPast.value < 90);
const canLoadFuture = computed(() => daysFuture.value < 90);

// Group events by date
const eventGroups = computed(() => {
  const groups: Array<{
    dateKey: string;
    date: Date;
    events: App.Entities.Calendar[];
    isPast: boolean;
    isToday: boolean;
  }> = [];

  // Filter events within date range
  const rangeEvents = props.events.filter((event) => {
    const eventDate = startOfDay(new Date(event.date));
    return !isBefore(eventDate, dateRange.value.start) && !isAfter(eventDate, dateRange.value.end);
  });

  // Group by date
  const groupMap = new Map<string, App.Entities.Calendar[]>();

  rangeEvents.forEach((event) => {
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
    // Sort events by time
    events.sort((a, b) => new Date(a.date).getTime() - new Date(b.date).getTime());

    groups.push({
      dateKey,
      date,
      events,
      isPast,
      isToday,
    });
  });

  // Sort groups by date
  groups.sort((a, b) => a.date.getTime() - b.date.getTime());

  return groups;
});

// Displayed groups (filter out past when showPast is false)
const hasPastEvents = computed(() => eventGroups.value.some(g => g.isPast));

const displayedGroups = computed(() => {
  if (showPast.value) return eventGroups.value;
  return eventGroups.value.filter(g => !g.isPast);
});

// Track if today marker has been shown
const todayMarkerShownRef = ref(false);

// Determine if today marker should be shown before a specific group index
const shouldShowTodayMarkerBefore = (groupIndex: number): boolean => {
  const groups = displayedGroups.value;
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
  const groups = displayedGroups.value;
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
  }
  catch {
    return '';
  }
};

// Load more actions - emit events to parent for API fetching
const loadMorePast = () => {
  daysPast.value += LOAD_MORE_DAYS;
  emit('loadPast');
};

const loadMoreFuture = () => {
  daysFuture.value += LOAD_MORE_DAYS;
  emit('loadFuture');
};
</script>

<style scoped>
.event-timeline-vertical {
  width: 100%;
}
</style>
