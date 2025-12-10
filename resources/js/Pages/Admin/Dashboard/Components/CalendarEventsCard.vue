<template>
  <Card :class="[
    'flex flex-col relative overflow-hidden shadow-sm dark:shadow-zinc-950/50',
    'border-zinc-200 dark:border-zinc-600 bg-gradient-to-br from-white to-zinc-50 dark:from-zinc-900 dark:to-zinc-950'
  ]" role="region" :aria-label="$t('Artėjantys renginiai')">
    <!-- Decorative accent -->
    <div class="absolute top-0 right-0 w-12 h-12 -mr-6 -mt-6 rotate-45 bg-vusa-red/30 dark:bg-vusa-red/20" aria-hidden="true" />

    <CardHeader class="pb-2 relative z-10">
      <div class="flex items-center justify-between">
        <CardTitle class="flex items-center gap-2 text-base">
          <CalendarDaysIcon class="h-5 w-5 text-vusa-red dark:text-vusa-red" aria-hidden="true" />
          {{ $t('Artėjantys renginiai') }}
        </CardTitle>
        <a 
          :href="calendarListUrl" 
          class="text-xs text-primary hover:underline"
        >
          {{ $t('Visi') }} →
        </a>
      </div>
    </CardHeader>

    <CardContent class="flex-1 relative z-10 pt-0">
      <div class="flex flex-col space-y-1">
        <a 
          v-for="event in eventsList" 
          :key="event.id"
          :href="getEventUrl(event)"
          class="flex items-center gap-3 py-2 px-2 -mx-2 rounded-md transition-colors hover:bg-zinc-100 dark:hover:bg-zinc-700/50"
        >
          <!-- Date badge -->
          <div class="flex flex-col items-center justify-center rounded-md bg-vusa-red/10 dark:bg-vusa-red/20 px-2.5 py-1.5 text-center min-w-[52px]">
            <span class="text-[10px] font-medium uppercase text-vusa-red dark:text-vusa-red">
              {{ formatEventMonth(event.date) }}
            </span>
            <span class="text-lg font-bold text-vusa-red dark:text-vusa-red leading-none">
              {{ formatEventDay(event.date) }}
            </span>
          </div>
          <!-- Content -->
          <div class="flex flex-col min-w-0 flex-1">
            <span class="text-zinc-800 dark:text-zinc-200 font-semibold text-sm leading-tight line-clamp-2 hover:text-vusa-red transition-colors">
              {{ getEventTitle(event) }}
            </span>
            <span v-if="getEventLocation(event)" class="text-zinc-500 dark:text-zinc-400 text-xs mt-1 truncate">
              <IFluentLocation16Regular class="inline-block w-3 h-3 mr-0.5" />
              {{ getEventLocation(event) }}
            </span>
          </div>
          <!-- Arrow indicator -->
          <ChevronRightIcon class="h-4 w-4 shrink-0 text-muted-foreground" />
        </a>
      </div>
    </CardContent>
  </Card>
</template>

<script setup lang="ts">
import { trans as $t } from "laravel-vue-i18n";
import { usePage } from "@inertiajs/vue3";
import { computed } from "vue";
import { format, parseISO } from "date-fns";
import { lt, enUS } from "date-fns/locale";
import { CalendarDays as CalendarDaysIcon, ChevronRight as ChevronRightIcon } from "lucide-vue-next";

import { Card, CardContent, CardHeader, CardTitle } from "@/Components/ui/card";

const props = defineProps<{
  eventsList: App.Entities.Calendar[];
}>();

const page = usePage();
const locale = computed(() => page.props.app.locale);
const dateLocale = computed(() => locale.value === 'lt' ? lt : enUS);

// Calendar list URL (external link to public site)
const calendarListUrl = computed(() => {
  const calendarString = locale.value === 'lt' ? 'renginiai' : 'events';
  return `https://www.vusa.lt/${locale.value}/${calendarString}`;
});

// Build the full URL for a single calendar event (external link)
const getEventUrl = (event: App.Entities.Calendar) => {
  const eventString = locale.value === 'lt' ? 'renginys' : 'event';
  return `https://www.vusa.lt/${locale.value}/${eventString}/${event.id}`;
};

// Get event title (handles translatable field)
const getEventTitle = (event: App.Entities.Calendar): string => {
  if (Array.isArray(event.title)) {
    // Find the translation for current locale
    const translations = event.title as Array<{ locale?: string; value?: string }>;
    const translation = translations.find((t) => t?.locale === locale.value);
    return translation?.value || translations[0]?.value || '';
  }
  return String(event.title || '');
};

// Get event location (handles translatable field)
const getEventLocation = (event: App.Entities.Calendar): string | null => {
  if (!event.location) return null;
  if (Array.isArray(event.location)) {
    const translations = event.location as Array<{ locale?: string; value?: string }>;
    const translation = translations.find((t) => t?.locale === locale.value);
    return translation?.value || translations[0]?.value || null;
  }
  return String(event.location);
};

// Format event month
const formatEventMonth = (dateStr: string) => {
  try {
    const date = parseISO(dateStr);
    return format(date, 'MMM', { locale: dateLocale.value });
  } catch {
    return '';
  }
};

// Format event day
const formatEventDay = (dateStr: string) => {
  try {
    const date = parseISO(dateStr);
    return format(date, 'd');
  } catch {
    return '';
  }
};
</script>
