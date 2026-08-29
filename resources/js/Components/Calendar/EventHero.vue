<template>
  <!-- Card variant: inset rounded photo panel with overlaid text (HeroCarousel look).
       Sits in the page's wrapper flow — no full-bleed breakout. -->
  <header
    v-if="variant === 'card'"
    data-slot="event-hero"
    class="relative isolate overflow-hidden rounded-2xl shadow-xl ring-1 ring-zinc-900/10 md:rounded-3xl dark:ring-white/10"
  >
    <div class="relative h-[38svh] min-h-[19rem] md:h-[44vh] md:min-h-[22rem]">
      <!-- Background photo -->
      <img
        v-if="heroImageUrl"
        :src="heroImageUrl"
        :alt="String(event.title)"
        class="absolute inset-0 h-full w-full object-cover"
      >
      <!-- Gradient placeholder for no image -->
      <div
        v-else
        class="absolute inset-0 bg-gradient-to-br from-zinc-900 via-zinc-800 to-zinc-900"
      >
        <div class="absolute inset-0 bg-[radial-gradient(ellipse_80%_50%_at_20%_40%,rgba(189,28,38,0.3),transparent),radial-gradient(ellipse_60%_40%_at_80%_60%,rgba(189,28,38,0.15),transparent)]" />
      </div>

      <!-- Scrim: uniform layer plus bottom-up gradient so white text stays readable -->
      <div class="absolute inset-0 bg-zinc-950/30" />
      <div class="absolute inset-0 bg-gradient-to-t from-zinc-950/80 via-zinc-950/25 to-transparent" />

      <div class="absolute inset-x-0 bottom-0 p-5 sm:p-7 lg:p-9">
        <div class="max-w-3xl space-y-3">
          <div class="flex flex-wrap items-center gap-2">
            <span
              v-if="event.tenant"
              class="px-2.5 py-1 text-xs font-medium rounded-full bg-vusa-red text-white"
            >
              {{ event.tenant.shortname }}
            </span>
            <span
              v-if="event.category"
              class="px-2.5 py-1 text-xs font-semibold uppercase tracking-wider rounded-full bg-white/20 text-white backdrop-blur-md border border-white/20"
            >
              {{ event.category.name }}
            </span>
            <span
              v-if="statusLabel"
              class="inline-flex items-center gap-2 px-2.5 py-1 text-xs font-semibold rounded-full backdrop-blur-md"
              :class="onImageStatusBadgeClasses"
            >
              <span class="relative flex h-2 w-2">
                <span
                  v-if="isLive"
                  class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75"
                  :class="statusDotClasses"
                />
                <span class="relative inline-flex rounded-full h-2 w-2" :class="statusDotClasses" />
              </span>
              {{ statusLabel }}
            </span>
          </div>

          <h1 class="text-2xl sm:text-3xl lg:text-4xl font-bold leading-tight text-white drop-shadow-sm">
            {{ event.title }}
          </h1>

          <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1 text-white">
            <span class="font-semibold drop-shadow-sm">{{ dateSpan.primary }}</span>
            <span class="text-sm text-white/75 drop-shadow-sm">{{ dateSpan.secondary }}</span>
            <span v-if="relativeLabel" class="text-sm text-white/60 drop-shadow-sm">
              · {{ relativeLabel }}
            </span>
          </div>

          <div v-if="$slots.actions" class="pt-2">
            <slot name="actions" :on-image="true" />
          </div>
        </div>
      </div>
    </div>
  </header>

  <!-- Split/minimal variants: light-context content. Split wraps it in a card with
       the photo beside it (stacked on mobile); minimal renders it bare on the page
       background, no photo at all. -->
  <header
    v-else
    data-slot="event-hero"
    :class="lightRootClasses"
  >
    <div
      v-if="variant === 'split' && heroImageUrl"
      class="relative aspect-[16/10] md:aspect-auto md:min-h-[20rem]"
    >
      <img
        :src="heroImageUrl"
        :alt="String(event.title)"
        class="absolute inset-0 h-full w-full object-cover"
      >
    </div>

    <div :class="lightContentClasses">
      <div class="flex flex-wrap items-center gap-2">
        <span
          v-if="event.tenant"
          class="px-2.5 py-1 text-xs font-medium rounded-full bg-vusa-red text-white"
        >
          {{ event.tenant.shortname }}
        </span>
        <span
          v-if="event.category"
          class="px-2.5 py-1 text-xs font-semibold uppercase tracking-wide rounded-full bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400"
        >
          {{ event.category.name }}
        </span>
        <span
          v-if="statusLabel"
          class="inline-flex items-center gap-2 px-2.5 py-1 text-xs font-semibold rounded-full"
          :class="lightStatusBadgeClasses"
        >
          <span class="relative flex h-2 w-2">
            <span
              v-if="isLive"
              class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75"
              :class="lightStatusDotClasses"
            />
            <span class="relative inline-flex rounded-full h-2 w-2" :class="lightStatusDotClasses" />
          </span>
          {{ statusLabel }}
        </span>
      </div>

      <h1 class="text-2xl sm:text-3xl font-bold leading-tight text-zinc-900 dark:text-zinc-100">
        {{ event.title }}
      </h1>

      <div class="flex flex-wrap items-baseline gap-x-2 gap-y-1">
        <span class="font-semibold text-zinc-900 dark:text-zinc-100">{{ dateSpan.primary }}</span>
        <span class="text-sm text-zinc-600 dark:text-zinc-400">{{ dateSpan.secondary }}</span>
        <span v-if="relativeLabel" class="text-sm text-zinc-500 dark:text-zinc-500">
          · {{ relativeLabel }}
        </span>
      </div>

      <div v-if="$slots.actions" class="pt-1">
        <slot name="actions" :on-image="false" />
      </div>
    </div>
  </header>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

import { formatEventDateSpan } from '@/Utils/IntlTime';
import { useEventStatus } from '@/Composables/useEventStatus';
import { LocaleEnum } from '@/Types/enums';

interface Props {
  event: App.Entities.Calendar;
}

const props = defineProps<Props>();
const page = usePage();
const locale = computed(() => (page.props.app.locale ?? LocaleEnum.LT) as LocaleEnum);

const { isLive, statusLabel, relativeLabel } = useEventStatus(() => props.event);

type HeroVariant = 'card' | 'split' | 'minimal';

const variant = computed<HeroVariant>(() => {
  const style = props.event.hero_style;
  return style === 'split' || style === 'minimal' ? style : 'card';
});

const heroImageUrl = computed(() => props.event.main_image_url || null);

const dateSpan = computed(() =>
  formatEventDateSpan(props.event.date, props.event.end_date, {
    allDay: props.event.is_all_day,
    locale: locale.value,
  }),
);

// Split is a card (photo beside content); minimal sits directly on the page
// background. Without a photo, split's content spans the whole card.
const lightRootClasses = computed(() =>
  variant.value === 'split'
    ? 'overflow-hidden rounded-2xl shadow-sm ring-1 ring-zinc-200 bg-white md:grid md:grid-cols-2 dark:bg-zinc-800/60 dark:ring-zinc-700/60'
    : 'space-y-3',
);

const lightContentClasses = computed(() =>
  variant.value === 'split'
    ? [
        'flex flex-col justify-center gap-3 p-6 sm:p-8',
        !heroImageUrl.value && 'md:col-span-2',
      ]
    : 'space-y-3',
);

const onImageStatusBadgeClasses = computed(() =>
  isLive.value
    ? 'bg-emerald-500/30 text-emerald-100 border border-emerald-400/40'
    : 'bg-zinc-500/30 text-zinc-200 border border-zinc-400/30',
);

const statusDotClasses = computed(() => (isLive.value ? 'bg-emerald-400' : 'bg-zinc-300'));

const lightStatusBadgeClasses = computed(() =>
  isLive.value
    ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400'
    : 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400',
);

const lightStatusDotClasses = computed(() =>
  isLive.value ? 'bg-emerald-500' : 'bg-zinc-400 dark:bg-zinc-500',
);
</script>

<style scoped>
/* Smooth reduced motion support */
@media (prefers-reduced-motion: reduce) {
  .animate-ping {
    animation: none;
  }
}
</style>
