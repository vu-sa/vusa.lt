<template>
  <article
    class="group flex gap-2 sm:gap-4 p-3 sm:p-4 rounded-lg border border-zinc-200 bg-white transition-all duration-200 hover:bg-zinc-50/80 hover:border-zinc-300 hover:shadow-sm dark:border-zinc-700 dark:bg-zinc-800 dark:hover:bg-zinc-800/80 dark:hover:border-zinc-600"
    :class="{ 'opacity-80': variant === 'past' }">
    <!-- Event Image or Date Badge -->
    <div class="flex-shrink-0">
      <div v-if="(event as any).main_image_url && variant !== 'compact'"
        class="w-12 h-10 sm:w-16 sm:h-12 lg:w-20 lg:h-14 rounded-lg overflow-hidden bg-zinc-100 dark:bg-zinc-700 relative">
        <img class="w-full h-full object-cover transition-transform duration-200 group-hover:scale-105"
          :class="{ 'opacity-0': imageLoadError }" :src="(event as any).main_image_url"
          :alt="getEventTitle(event)" @error="handleImageError" @load="handleImageLoad">
        <div v-if="imageLoadError"
          class="absolute inset-0 flex items-center justify-center bg-gradient-to-br from-vusa-red/10 to-vusa-red/20 dark:from-vusa-red/20 dark:to-vusa-red/30">
          <IFluentCalendarLtr20Regular class="w-4 h-4 sm:w-5 sm:h-5 lg:w-6 lg:h-6 text-vusa-red dark:text-vusa-red" />
        </div>
      </div>
      <div v-else-if="variant !== 'compact'"
        class="w-12 h-10 sm:w-16 sm:h-12 lg:w-20 lg:h-14 rounded-lg bg-gradient-to-br from-vusa-red/10 to-vusa-red/20 dark:from-vusa-red/20 dark:to-vusa-red/30 flex items-center justify-center">
        <IFluentCalendarLtr20Regular class="w-4 h-4 sm:w-5 sm:h-5 lg:w-6 lg:h-6 text-vusa-red dark:text-vusa-red" />
      </div>
      <!-- For compact variant, show smaller image or icon -->
      <div v-else-if="(event as any).main_image_url"
        class="w-10 h-8 sm:w-12 sm:h-10 rounded-lg overflow-hidden bg-zinc-100 dark:bg-zinc-700 relative">
        <img class="w-full h-full object-cover transition-transform duration-200 group-hover:scale-105"
          :class="{ 'opacity-0': imageLoadErrorCompact }" :src="(event as any).main_image_url"
          :alt="getEventTitle(event)" @error="handleImageErrorCompact" @load="handleImageLoadCompact">
        <div v-if="imageLoadErrorCompact"
          class="absolute inset-0 flex items-center justify-center bg-vusa-red/10 dark:bg-vusa-red/20">
          <IFluentCalendarLtr16Regular class="w-3 h-3 sm:w-4 sm:h-4 text-vusa-red dark:text-vusa-red" />
        </div>
      </div>
      <div v-else
        class="w-10 h-8 sm:w-12 sm:h-10 rounded-lg bg-vusa-red/10 dark:bg-vusa-red/20 flex items-center justify-center">
        <IFluentCalendarLtr16Regular class="w-3 h-3 sm:w-4 sm:h-4 text-vusa-red dark:text-vusa-red" />
      </div>
    </div>

    <!-- Event Content -->
    <div class="flex-1 min-w-0">
      <!-- Header with badges -->
      <div class="flex flex-wrap items-center gap-1 sm:gap-2 mb-1 sm:mb-2">
        <!-- Category badge -->
        <span v-if="event.category && showBadges"
          class="inline-flex items-center rounded-full px-1.5 sm:px-2 py-0.5 text-xs font-medium bg-zinc-100 text-zinc-700 dark:bg-zinc-700/70 dark:text-zinc-300">
          {{ event.category.name }}
        </span>

        <!-- Tenant badge -->
        <span v-if="event.tenant && showBadges"
          class="inline-flex items-center rounded-full px-1.5 sm:px-2 py-0.5 text-xs font-medium bg-blue-100/70 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300/90">
          {{ event.tenant.shortname }}
        </span>
      </div>

      <!-- Event Title -->
      <h3 class="font-semibold mb-1 sm:mb-2 transition-colors duration-200 hover:text-vusa-red dark:hover:text-vusa-red"
        :class="[
          variant === 'past'
            ? 'text-zinc-700 dark:text-zinc-300'
            : 'text-zinc-900 dark:text-zinc-100',
          variant === 'compact' ? 'text-sm sm:text-base line-clamp-2' : 'text-base sm:text-lg line-clamp-2 sm:line-clamp-1'
        ]">
        <Link :href="route('calendar.event', { calendar: event.id, lang: $page.props.app.locale })">
          {{ getEventTitle(event) }}
        </Link>
      </h3>

      <!-- Event Metadata -->
      <div class="flex flex-col sm:flex-row sm:flex-wrap gap-x-3 sm:gap-x-4 gap-y-1 text-xs sm:text-sm mb-2 sm:mb-3"
        :class="variant === 'past' ? 'text-zinc-500 dark:text-zinc-400' : 'text-zinc-600 dark:text-zinc-400'">
        <!-- Date -->
        <div class="flex items-center gap-1">
          <IFluentCalendarLtr16Regular class="w-3 h-3 sm:w-4 sm:h-4 flex-shrink-0" />
          <span class="break-words">{{ formatEventDateTime(event.date, event.end_date || undefined) }}</span>
        </div>

        <!-- Location -->
        <div v-if="event.location" class="flex items-center gap-1 min-w-0">
          <IFluentLocation16Regular class="w-3 h-3 sm:w-4 sm:h-4 flex-shrink-0" />
          <span class="truncate">{{ getEventLocation(event) }}</span>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="flex flex-col sm:flex-row items-start sm:items-center gap-2">
        <Button as="a" :href="route('calendar.event', { calendar: event.id, lang: $page.props.app.locale })"
          :variant="variant === 'past' ? 'outline' : 'default'" :size="variant === 'compact' ? 'sm' : 'default'"
          class="gap-1 w-full sm:w-auto">
          <IFluentInfo16Regular class="w-3 h-3 sm:w-4 sm:h-4" />
          {{ variant === 'past' ? $t('Peržiūrėti') : $t('Daugiau') }}
        </Button>

        <!-- Social/Calendar Actions for upcoming events - Hidden on mobile -->
        <div v-if="variant !== 'past'" class="hidden sm:flex gap-1">
          <Button v-if="googleLink" as="a" :href="googleLink" target="_blank" variant="ghost"
            :size="variant === 'compact' ? 'sm' : 'default'" :title="$t('Pridėti į Google kalendorių')">
            <IMdiGoogle class="w-4 h-4" />
          </Button>

          <Button v-if="event.facebook_url" as="a" :href="event.facebook_url" target="_blank" variant="ghost"
            :size="variant === 'compact' ? 'sm' : 'default'" :title="$t('Facebook renginys')">
            <IMdiFacebook class="w-4 h-4" />
          </Button>
        </div>
      </div>
    </div>
  </article>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

import Button from '@/Components/ui/button/Button.vue';
import { formatStaticTime } from '@/Utils/IntlTime';

const props = withDefaults(defineProps<{
  event: App.Entities.Calendar;
  variant?: 'upcoming' | 'past' | 'compact';
  showBadges?: boolean;
  googleLink?: string;
}>(), {
  variant: 'upcoming',
  showBadges: true,
});

const page = usePage();

// Image loading state
const imageLoadError = ref(false);
const imageLoadErrorCompact = ref(false);

// Image error handlers
const handleImageError = () => {
  imageLoadError.value = true;
};

const handleImageLoad = () => {
  imageLoadError.value = false;
};

const handleImageErrorCompact = () => {
  imageLoadErrorCompact.value = true;
};

const handleImageLoadCompact = () => {
  imageLoadErrorCompact.value = false;
};

// Helper functions for handling event data types
const getEventTitle = (event: App.Entities.Calendar): string => {
  return Array.isArray(event.title) ? event.title.join(' ') : (event.title || '');
};

const getEventLocation = (event: App.Entities.Calendar): string => {
  return Array.isArray(event.location) ? event.location.join(' ') : (event.location || '');
};

// Format event date and time
const formatEventDateTime = (startDate: string, endDate?: string): string => {
  const start = new Date(startDate);
  const { locale } = page.props.app;

  if (endDate) {
    const end = new Date(endDate);
    // If same day, show "Jan 15, 10:00 - 15:00"
    if (start.toDateString() === end.toDateString()) {
      const dateStr = formatStaticTime(start, { dateStyle: 'medium' }, locale);
      const startTime = formatStaticTime(start, { timeStyle: 'short' }, locale);
      const endTime = formatStaticTime(end, { timeStyle: 'short' }, locale);
      return `${dateStr}, ${startTime} - ${endTime}`;
    }
    // Different days: "Jan 15, 10:00 - Jan 16, 15:00"
    return `${formatStaticTime(start, { dateStyle: 'medium', timeStyle: 'short' }, locale)} - ${formatStaticTime(end, { dateStyle: 'medium', timeStyle: 'short' }, locale)}`;
  }

  return formatStaticTime(start, { dateStyle: 'medium', timeStyle: 'short' }, locale);
};
</script>
