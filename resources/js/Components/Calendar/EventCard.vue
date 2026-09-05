<template>
  <article
    class="group flex h-full flex-col overflow-hidden rounded-xl border border-zinc-200 bg-white transition-all duration-200 hover:border-zinc-300 hover:shadow-md dark:border-zinc-700 dark:bg-zinc-800 dark:hover:border-zinc-600"
    :class="{ 'opacity-80': variant === 'past' }"
  >
    <!-- Image / fallback: a wide band, not a poster — the card's balance comes from the content below, not an oversized photo -->
    <Link :href="route('calendar.event', { calendar: event.id, lang: $page.props.app.locale })" class="block aspect-video w-full shrink-0 overflow-hidden bg-zinc-100 dark:bg-zinc-700">
      <div v-if="(event as any).main_image_url" class="relative h-full w-full">
        <img
          class="h-full w-full object-cover transition-transform duration-200 group-hover:scale-105"
          :class="{ 'opacity-0': imageLoadError }"
          :src="(event as any).main_image_url"
          :alt="getEventTitle(event)"
          :style="{ objectPosition: event.main_image_focal_point ?? '50% 30%' }"
          @error="imageLoadError = true"
          @load="imageLoadError = false"
        >
        <div
          v-if="imageLoadError"
          class="absolute inset-0 flex items-center justify-center bg-gradient-to-br from-vusa-red/10 to-vusa-red/20 dark:from-vusa-red/20 dark:to-vusa-red/30"
        >
          <IFluentCalendarLtr20Regular class="h-8 w-8 text-vusa-red" />
        </div>
      </div>
      <div v-else class="flex h-full w-full items-center justify-center bg-gradient-to-br from-vusa-red/10 to-vusa-red/20 dark:from-vusa-red/20 dark:to-vusa-red/30">
        <IFluentCalendarLtr20Regular class="h-8 w-8 text-vusa-red" />
      </div>
    </Link>

    <!-- Content -->
    <div class="flex flex-1 flex-col gap-2.5 p-4">
      <!-- Badges -->
      <div v-if="showBadges && (event.category || event.tenant)" class="flex flex-wrap items-center gap-1.5">
        <span v-if="event.category" class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium bg-zinc-100 text-zinc-700 dark:bg-zinc-700/70 dark:text-zinc-300">
          {{ event.category.name }}
        </span>
        <span v-if="event.tenant" class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium bg-blue-100/70 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300/90">
          {{ event.tenant.shortname }}
        </span>
      </div>

      <!-- Title -->
      <h3
        class="font-semibold leading-snug line-clamp-2 transition-colors duration-200 hover:text-vusa-red dark:hover:text-vusa-red"
        :class="variant === 'past' ? 'text-zinc-700 dark:text-zinc-300' : 'text-zinc-900 dark:text-zinc-100'"
      >
        <Link :href="route('calendar.event', { calendar: event.id, lang: $page.props.app.locale })">
          {{ getEventTitle(event) }}
        </Link>
      </h3>

      <!-- Metadata -->
      <div class="flex flex-col gap-1.5 text-xs" :class="variant === 'past' ? 'text-zinc-500 dark:text-zinc-400' : 'text-zinc-600 dark:text-zinc-400'">
        <div class="flex items-center gap-1.5">
          <IFluentCalendarLtr16Regular class="h-3.5 w-3.5 shrink-0" />
          <span class="break-words">{{ formatEventDateTime(event) }}</span>
        </div>

        <div v-if="event.is_remote" class="flex items-center gap-1.5">
          <IFluentGlobe16Regular class="h-3.5 w-3.5 shrink-0" />
          <span>{{ $t('Nuotolinis renginys') }}</span>
        </div>
        <div v-else-if="event.location" class="flex items-center gap-1.5 min-w-0">
          <IFluentLocation16Regular class="h-3.5 w-3.5 shrink-0" />
          <span class="truncate">{{ getEventLocation(event) }}</span>
        </div>
      </div>

      <!-- Actions -->
      <div class="mt-auto flex items-center gap-2 pt-1">
        <Button as="a" :href="route('calendar.event', { calendar: event.id, lang: $page.props.app.locale })"
          :variant="variant === 'past' ? 'outline' : 'default'" size="sm" class="flex-1 gap-1.5">
          <IFluentInfo16Regular class="h-3.5 w-3.5" />
          {{ variant === 'past' ? $t('Peržiūrėti') : $t('Daugiau') }}
        </Button>

        <template v-if="variant !== 'past'">
          <Button v-if="googleLink" as="a" :href="googleLink" target="_blank" variant="ghost" size="sm" :title="$t('Pridėti į Google kalendorių')">
            <ISimpleIconsGoogle class="h-4 w-4" />
          </Button>

          <Button v-if="event.facebook_url" as="a" :href="event.facebook_url" target="_blank" variant="ghost" size="sm" :title="$t('Facebook renginys')">
            <ISimpleIconsFacebook class="h-4 w-4" />
          </Button>
        </template>
      </div>
    </div>
  </article>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { Link, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

import Button from '@/Components/ui/button/Button.vue';
import { formatEventDateSpan } from '@/Utils/IntlTime';

const page = usePage();

const props = withDefaults(defineProps<{
  event: App.Entities.Calendar;
  variant?: 'upcoming' | 'past' | 'compact';
  showBadges?: boolean;
  googleLink?: string;
}>(), {
  variant: 'upcoming',
  showBadges: true,
});

const imageLoadError = ref(false);

const getEventTitle = (event: App.Entities.Calendar): string =>
  Array.isArray(event.title) ? event.title.join(' ') : (event.title || '');

const getEventLocation = (event: App.Entities.Calendar): string =>
  Array.isArray(event.location) ? event.location.join(' ') : (event.location || '');

/** Multi-day events read as one collapsed span, e.g. "2026 m. rugpjūčio 25–27 d. · 10:00 → 18:00". */
const formatEventDateTime = (event: App.Entities.Calendar): string => {
  const span = formatEventDateSpan(event.date, event.end_date, {
    allDay: event.is_all_day,
    locale: page.props.app.locale,
  });

  return `${span.primary} · ${span.secondary}`;
};
</script>
