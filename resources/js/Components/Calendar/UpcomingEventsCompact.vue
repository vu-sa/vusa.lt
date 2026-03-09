<template>
  <div class="upcoming-events-compact">
    <!-- Header -->
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300 flex items-center gap-2">
        <IFluentCalendarLtr16Regular class="w-4 h-4 text-vusa-red" />
        {{ $t('Artėjantys renginiai') }}
      </h3>
      <a 
        :href="route('calendar.list', { lang: locale })"
        class="text-xs text-vusa-red hover:underline"
      >
        {{ $t('Visi') }} →
      </a>
    </div>

    <!-- Events list -->
    <div class="space-y-2">
      <a
        v-for="(event, index) in visibleEvents"
        :key="event.id"
        :href="route('calendar.event', { calendar: event.id, lang: locale })"
        class="group flex items-center gap-3 p-2.5 -mx-2.5 rounded-lg transition-all duration-200 hover:bg-zinc-100 dark:hover:bg-zinc-700/50"
        :class="index === 0 ? 'bg-vusa-red/5 dark:bg-vusa-red/10' : ''"
      >
        <!-- Date badge -->
        <div 
          class="flex flex-col items-center justify-center rounded-lg px-2 py-1.5 text-center min-w-[44px]"
          :class="index === 0 
            ? 'bg-vusa-red text-white' 
            : 'bg-zinc-100 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300'"
        >
          <span class="text-[9px] font-medium uppercase leading-none">
            {{ formatMonth(event.date) }}
          </span>
          <span class="text-base font-bold leading-none mt-0.5">
            {{ formatDay(event.date) }}
          </span>
        </div>

        <!-- Event thumbnail -->
        <div class="w-10 h-10 rounded-lg overflow-hidden flex-shrink-0 bg-zinc-100 dark:bg-zinc-700">
          <img 
            v-if="getEventImage(event)"
            :src="getEventImage(event)!"
            :alt="getEventTitle(event)"
            class="w-full h-full object-cover transition-transform duration-200 group-hover:scale-105"
            loading="lazy"
          >
          <div 
            v-else 
            class="w-full h-full bg-gradient-to-br from-vusa-red/10 to-vusa-red/30 flex items-center justify-center"
          >
            <IFluentCalendarLtr16Regular class="w-4 h-4 text-vusa-red/60" />
          </div>
        </div>

        <!-- Event info -->
        <div class="flex-1 min-w-0">
          <p 
            class="text-sm font-medium line-clamp-2 leading-tight transition-colors group-hover:text-vusa-red"
            :class="index === 0 ? 'text-zinc-900 dark:text-zinc-100' : 'text-zinc-700 dark:text-zinc-300'"
          >
            {{ getEventTitle(event) }}
          </p>
          <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5 flex items-center gap-1">
            <IFluentClock12Regular class="w-3 h-3" />
            {{ formatTime(event.date) }}
          </p>
        </div>

        <!-- Next badge for first event -->
        <div 
          v-if="index === 0"
          class="absolute -top-1 -right-1 px-1.5 py-0.5 bg-vusa-red text-white text-[8px] font-bold rounded-full shadow-sm"
        >
          {{ $t('Sekantis') }}
        </div>
      </a>

      <!-- Show more if there are hidden events -->
      <a
        v-if="hiddenCount > 0"
        :href="route('calendar.list', { lang: locale })"
        class="flex items-center justify-center gap-2 py-2 text-xs font-medium text-zinc-500 dark:text-zinc-400 hover:text-vusa-red transition-colors"
      >
        <span>+{{ hiddenCount }} {{ $t('daugiau renginių') }}</span>
        <IFluentChevronRight12Regular class="w-3 h-3" />
      </a>
    </div>

    <!-- Empty state -->
    <div 
      v-if="events.length === 0"
      class="text-center py-6"
    >
      <div class="w-12 h-12 mx-auto rounded-full bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center mb-3">
        <IFluentCalendarEmpty16Regular class="w-6 h-6 text-zinc-400" />
      </div>
      <p class="text-sm text-zinc-500 dark:text-zinc-400">{{ $t('Nėra artėjančių renginių') }}</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { trans as $t } from "laravel-vue-i18n";
import { computed } from "vue";
import { format, parseISO } from "date-fns";
import { lt, enUS } from "date-fns/locale";

const props = withDefaults(defineProps<{
  events: App.Entities.Calendar[];
  locale: string;
  maxVisible?: number;
  excludeEventId?: number;
}>(), {
  maxVisible: 4,
});

const dateLocale = computed(() => props.locale === 'lt' ? lt : enUS);

// Filter and sort upcoming events
const upcomingEvents = computed(() => {
  const now = new Date();
  return props.events
    .filter(e => {
      if (props.excludeEventId && e.id === props.excludeEventId) return false;
      return new Date(e.date) >= now;
    })
    .sort((a, b) => new Date(a.date).getTime() - new Date(b.date).getTime());
});

const visibleEvents = computed(() => upcomingEvents.value.slice(0, props.maxVisible));
const hiddenCount = computed(() => Math.max(0, upcomingEvents.value.length - props.maxVisible));

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

// Date formatting
const formatMonth = (dateStr: string): string => {
  try {
    const date = parseISO(dateStr);
    return format(date, 'MMM', { locale: dateLocale.value });
  } catch {
    return '';
  }
};

const formatDay = (dateStr: string): string => {
  try {
    const date = parseISO(dateStr);
    return format(date, 'd');
  } catch {
    return '';
  }
};

const formatTime = (dateStr: string): string => {
  try {
    const date = parseISO(dateStr);
    return format(date, 'HH:mm');
  } catch {
    return '';
  }
};
</script>

<style scoped>
.upcoming-events-compact a {
  position: relative;
}
</style>
