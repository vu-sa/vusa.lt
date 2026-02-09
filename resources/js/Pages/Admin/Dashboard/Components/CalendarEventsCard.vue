<template>
  <Card :class="dashboardCardClasses" role="region" :aria-label="$t('Artėjantys renginiai')">
    <!-- Decorative accent -->
    <div class="absolute top-0 right-0 w-12 h-12 -mr-6 -mt-6 rotate-45 bg-vusa-red/30 dark:bg-vusa-red/20"
      aria-hidden="true" />

    <CardHeader class="pb-2 relative z-10">
      <div class="flex items-center justify-between">
        <CardTitle class="flex items-center gap-2 text-base">
          <CalendarDaysIcon class="h-5 w-5 text-vusa-red dark:text-vusa-red" aria-hidden="true" />
          {{ $t('Artėjantys renginiai') }}
        </CardTitle>
        <a :href="route('calendar.list', { lang: locale === 'lt' ? 'lt' : 'en' })"
          class="text-xs text-primary hover:underline">
          {{ $t('Visi') }} →
        </a>
      </div>
    </CardHeader>

    <CardContent class="flex-1 relative z-10 pt-0">
      <!-- Timeline-inspired layout with vertical line -->
      <div class="relative pl-4">
        <!-- Vertical timeline line -->
        <div class="absolute left-0 top-2 bottom-2 w-0.5 bg-gradient-to-b from-vusa-red via-vusa-red/50 to-zinc-200 dark:to-zinc-700 rounded-full" />

        <div class="flex flex-col space-y-1">
          <a v-for="(event, index) in eventsList" :key="event.id" :href="route('calendar.event', {
            calendar: event.id,
            lang: locale === 'lt' ? 'lt' : 'en',
          })"
            class="group relative flex items-center gap-3 py-2 px-2 -mx-2 rounded-md transition-colors hover:bg-zinc-100 dark:hover:bg-zinc-700/50">

            <!-- Timeline dot -->
            <div
              class="absolute -left-4 w-2.5 h-2.5 rounded-full border-2 border-white dark:border-zinc-900 shadow-sm transition-all duration-200"
              :class="index === 0
                ? 'bg-vusa-red scale-110'
                : 'bg-zinc-300 dark:bg-zinc-600 group-hover:bg-vusa-red group-hover:scale-110'"
            />

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
                class="w-full h-full flex items-center justify-center bg-gradient-to-br from-vusa-red/10 to-vusa-red/30"
              >
                <CalendarDaysIcon class="w-4 h-4 text-vusa-red/60" />
              </div>
            </div>

            <!-- Date badge -->
            <div
              class="flex flex-col items-center justify-center rounded-md px-2 py-1 text-center min-w-[44px]"
              :class="index === 0
                ? 'bg-vusa-red text-white'
                : 'bg-vusa-red/10 dark:bg-vusa-red/20 text-vusa-red'"
            >
              <span class="text-[9px] font-medium uppercase leading-none">
                {{ formatEventMonth(event.date) }}
              </span>
              <span class="text-sm font-bold leading-none mt-0.5">
                {{ formatEventDay(event.date) }}
              </span>
            </div>

            <!-- Content -->
            <div class="flex flex-col min-w-0 flex-1">
              <span
                class="text-zinc-800 dark:text-zinc-200 font-semibold text-sm leading-tight line-clamp-2 group-hover:text-vusa-red transition-colors">
                {{ getEventTitle(event) }}
              </span>
              <span v-if="getEventLocation(event)" class="text-zinc-500 dark:text-zinc-400 text-xs mt-0.5 truncate flex items-center gap-0.5">
                <IFluentLocation12Regular class="w-3 h-3 flex-shrink-0" />
                {{ getEventLocation(event) }}
              </span>
            </div>

            <!-- Arrow indicator -->
            <ChevronRightIcon class="h-4 w-4 shrink-0 text-muted-foreground group-hover:text-vusa-red transition-colors" />

            <!-- "Next" badge for first event -->
            <div
              v-if="index === 0"
              class="absolute -top-1 right-0 px-1.5 py-0.5 bg-vusa-red text-white text-[8px] font-bold rounded-full shadow-sm"
            >
              {{ $t('Artėja') }}
            </div>
          </a>
        </div>
      </div>

      <!-- Empty state -->
      <div v-if="eventsList.length === 0" class="text-center py-6">
        <div class="w-12 h-12 mx-auto rounded-full bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center mb-3">
          <CalendarDaysIcon class="w-6 h-6 text-zinc-400" />
        </div>
        <p class="text-sm text-zinc-500 dark:text-zinc-400">
          {{ $t('Nėra artėjančių renginių') }}
        </p>
      </div>
    </CardContent>
  </Card>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { format, parseISO } from 'date-fns';
import { lt, enUS } from 'date-fns/locale';
import { CalendarDays as CalendarDaysIcon, ChevronRight as ChevronRightIcon } from 'lucide-vue-next';

import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import { dashboardCardClasses } from '@/Composables/useDashboardCardStyles';

const props = defineProps<{
  eventsList: App.Entities.Calendar[];
}>();

const page = usePage();
const locale = computed(() => page.props.app.locale);
const dateLocale = computed(() => locale.value === 'lt' ? lt : enUS);

// Get event image
const getEventImage = (event: App.Entities.Calendar): string | null => {
  // Use main_image_url accessor which handles fallback to first gallery image
  return (event as any).main_image_url || null;
};

// Get event title (handles translatable field)
const getEventTitle = (event: App.Entities.Calendar): string => {
  if (Array.isArray(event.title)) {
    // Find the translation for current locale
    const translations = event.title as Array<{ locale?: string; value?: string }>;
    const translation = translations.find(t => t?.locale === locale.value);
    return translation?.value || translations[0]?.value || '';
  }
  return String(event.title || '');
};

// Get event location (handles translatable field)
const getEventLocation = (event: App.Entities.Calendar): string | null => {
  if (!event.location) return null;
  if (Array.isArray(event.location)) {
    const translations = event.location as Array<{ locale?: string; value?: string }>;
    const translation = translations.find(t => t?.locale === locale.value);
    return translation?.value || translations[0]?.value || null;
  }
  return String(event.location);
};

// Format event month
const formatEventMonth = (dateStr: string) => {
  try {
    const date = parseISO(dateStr);
    return format(date, 'MMM', { locale: dateLocale.value });
  }
  catch {
    return '';
  }
};

// Format event day
const formatEventDay = (dateStr: string) => {
  try {
    const date = parseISO(dateStr);
    return format(date, 'd');
  }
  catch {
    return '';
  }
};
</script>
