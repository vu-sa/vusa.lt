<template>
  <div class="event-hover-card">
    <!-- Event image header -->
    <div class="relative h-32 overflow-hidden bg-gradient-to-br from-zinc-100 to-zinc-200 dark:from-zinc-800 dark:to-zinc-900">
      <img 
        v-if="eventImage"
        :src="eventImage"
        :alt="eventTitle"
        class="w-full h-full object-cover"
      >
      <div 
        v-else 
        class="w-full h-full flex items-center justify-center bg-gradient-to-br from-vusa-red/10 to-vusa-red/30 dark:from-vusa-red/20 dark:to-vusa-red/40"
      >
        <IFluentCalendarLtr24Regular class="w-12 h-12 text-vusa-red/50" />
      </div>

      <!-- Category badge -->
      <div 
        v-if="event.category"
        class="absolute top-2 left-2 px-2 py-0.5 text-xs font-medium rounded-full bg-white/90 dark:bg-zinc-900/90 text-zinc-700 dark:text-zinc-300 backdrop-blur-sm"
      >
        {{ event.category.name }}
      </div>

      <!-- Date badge -->
      <div class="absolute bottom-2 right-2 flex flex-col items-center px-2.5 py-1.5 rounded-lg bg-white/95 dark:bg-zinc-900/95 backdrop-blur-sm shadow-sm">
        <span class="text-[10px] font-medium uppercase text-vusa-red">
          {{ formatMonth }}
        </span>
        <span class="text-lg font-bold text-vusa-red leading-none">
          {{ formatDay }}
        </span>
      </div>
    </div>

    <!-- Event content -->
    <div class="p-4 space-y-3">
      <!-- Title -->
      <h4 class="font-semibold text-zinc-900 dark:text-zinc-100 line-clamp-2 leading-tight">
        {{ eventTitle }}
      </h4>

      <!-- Metadata -->
      <div class="space-y-1.5 text-sm text-zinc-600 dark:text-zinc-400">
        <!-- Time -->
        <div class="flex items-center gap-2">
          <IFluentClock16Regular class="w-4 h-4 flex-shrink-0 text-vusa-red/70" />
          <span>{{ formatTime }}</span>
        </div>

        <!-- Location -->
        <div v-if="eventLocation" class="flex items-center gap-2">
          <IFluentLocation16Regular class="w-4 h-4 flex-shrink-0 text-vusa-red/70" />
          <span class="truncate">{{ eventLocation }}</span>
        </div>

        <!-- Tenant -->
        <div v-if="event.tenant" class="flex items-center gap-2">
          <IFluentBuilding16Regular class="w-4 h-4 flex-shrink-0 text-vusa-red/70" />
          <span>{{ event.tenant.shortname }}</span>
        </div>
      </div>

      <!-- Action button -->
      <Button 
        as="a" 
        :href="route('calendar.event', { calendar: event.id, lang: locale })"
        class="w-full"
        size="sm"
      >
        <IFluentOpen16Regular class="w-4 h-4 mr-1.5" />
        {{ $t('Peržiūrėti renginį') }}
      </Button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { trans as $t } from "laravel-vue-i18n";
import { computed } from "vue";
import { format, parseISO } from "date-fns";
import { lt, enUS } from "date-fns/locale";

import { Button } from "@/Components/ui/button";

const props = defineProps<{
  event: App.Entities.Calendar;
  locale: string;
}>();

const dateLocale = computed(() => props.locale === 'lt' ? lt : enUS);

// Computed values for event data
const eventImage = computed((): string | null => {
  const images = (props.event as any).images;
  if (!images) return null;
  
  // Handle both array and object structures
  if (Array.isArray(images) && images.length > 0) {
    return images[0].original_url;
  }
  
  // If it's an object (keyed by UUID), get the first value
  if (typeof images === 'object') {
    const values = Object.values(images) as any[];
    if (values.length > 0 && values[0]?.original_url) {
      return values[0].original_url;
    }
  }
  
  return null;
});

const eventTitle = computed((): string => {
  if (Array.isArray(props.event.title)) {
    const translations = props.event.title as Array<{ locale?: string; value?: string }>;
    const translation = translations.find(t => t?.locale === props.locale);
    return translation?.value || translations[0]?.value || '';
  }
  return String(props.event.title || '');
});

const eventLocation = computed((): string | null => {
  if (!props.event.location) return null;
  if (Array.isArray(props.event.location)) {
    const translations = props.event.location as Array<{ locale?: string; value?: string }>;
    const translation = translations.find(t => t?.locale === props.locale);
    return translation?.value || translations[0]?.value || null;
  }
  return String(props.event.location);
});

// Date formatting
const formatMonth = computed(() => {
  try {
    const date = parseISO(props.event.date);
    return format(date, 'MMM', { locale: dateLocale.value });
  } catch {
    return '';
  }
});

const formatDay = computed(() => {
  try {
    const date = parseISO(props.event.date);
    return format(date, 'd');
  } catch {
    return '';
  }
});

const formatTime = computed(() => {
  try {
    const date = parseISO(props.event.date);
    const timeStr = format(date, 'HH:mm');
    
    if (props.event.end_date) {
      const endDate = parseISO(props.event.end_date);
      const endTimeStr = format(endDate, 'HH:mm');
      return `${timeStr} - ${endTimeStr}`;
    }
    
    return timeStr;
  } catch {
    return '';
  }
});
</script>

<style scoped>
.event-hover-card {
  background-color: white;
}

:root.dark .event-hover-card {
  background-color: rgb(24 24 27); /* zinc-900 */
}
</style>
