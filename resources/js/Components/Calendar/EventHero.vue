<template>
  <div class="event-hero-wrapper">
    <!-- Desktop Hero (lg+) - Immersive full-bleed design -->
    <header
      class="hidden lg:block relative overflow-hidden"
      :class="heroContainerClasses"
    >
      <!-- Background Layer -->
      <div class="absolute inset-0">
        <!-- Image Background -->
        <div
          v-if="hasImage"
          class="absolute inset-0 bg-cover bg-center"
          :style="{ backgroundImage: `url(${heroImageUrl})` }"
        />

        <!-- Gradient Placeholder for No Image -->
        <div
          v-else
          class="absolute inset-0 bg-gradient-to-br from-zinc-900 via-zinc-800 to-zinc-900"
        >
          <!-- Animated mesh gradient overlay -->
          <div class="absolute inset-0 opacity-60 bg-[radial-gradient(ellipse_80%_50%_at_20%_40%,rgba(189,28,38,0.3),transparent),radial-gradient(ellipse_60%_40%_at_80%_60%,rgba(189,28,38,0.15),transparent)]" />

          <!-- Geometric pattern -->
          <svg class="absolute inset-0 w-full h-full opacity-[0.04]" xmlns="http://www.w3.org/2000/svg">
            <defs>
              <pattern id="hero-grid" width="60" height="60" patternUnits="userSpaceOnUse">
                <path d="M 60 0 L 0 0 0 60" fill="none" stroke="white" stroke-width="0.5" />
              </pattern>
            </defs>
            <rect width="100%" height="100%" fill="url(#hero-grid)" />
          </svg>

          <!-- Decorative accent shapes -->
          <div class="absolute top-1/4 -right-20 w-80 h-80 rounded-full bg-vusa-red/10 blur-3xl" />
          <div class="absolute -bottom-20 left-1/4 w-96 h-96 rounded-full bg-vusa-red/5 blur-3xl" />
        </div>

        <!-- Gradient overlay for text readability -->
        <div class="absolute inset-0 bg-gradient-to-t from-black via-black/70 to-black/40" />
      </div>

      <!-- Content Container - Absolute positioned at bottom -->
      <div class="absolute inset-x-0 bottom-0 z-10 pb-10 lg:pb-14 px-8">
        <div class="max-w-7xl mx-auto">
          <div class="max-w-4xl space-y-4">
            <!-- Category/Tenant badges -->
            <div class="flex flex-wrap items-center gap-3">
              <span
                v-if="event.tenant"
                class="px-3 py-1.5 text-xs font-medium rounded-full bg-vusa-red text-white"
              >
                {{ event.tenant.shortname }}
              </span>
              <span
                v-if="event.category"
                class="px-3 py-1.5 text-xs font-semibold uppercase tracking-wider rounded-full bg-white/20 text-white backdrop-blur-md border border-white/20"
              >
                {{ event.category.name }}
              </span>

              <!-- Status: only the states that change what you can do -->
              <span
                v-if="statusLabel"
                class="inline-flex items-center gap-2 px-3 py-1.5 text-xs font-semibold rounded-full backdrop-blur-md"
                :class="statusBadgeClasses"
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

            <!-- Title -->
            <h1 class="text-4xl sm:text-5xl lg:text-6xl xl:text-7xl font-black leading-[1.05] text-white tracking-tight drop-shadow-[0_2px_4px_rgba(0,0,0,0.5)]">
              {{ event.title }}
            </h1>

            <!-- Date -->
            <div class="flex flex-wrap items-baseline gap-x-3 gap-y-1 text-white">
              <span class="text-lg font-semibold drop-shadow-sm">{{ dateSpan.primary }}</span>
              <span class="text-base text-white/80 drop-shadow-sm">{{ dateSpan.secondary }}</span>
              <span v-if="relativeLabel" class="text-sm text-white/60 drop-shadow-sm">
                · {{ relativeLabel }}
              </span>
            </div>

            <!-- Action Buttons Slot -->
            <div v-if="$slots.actions" class="pt-2">
              <slot name="actions" :on-image="true" />
            </div>
          </div>
        </div>
      </div>
    </header>

    <!-- Mobile Hero (< lg) - Clean stacked design -->
    <div class="lg:hidden">
      <!-- Image or gradient header -->
      <div class="relative">
        <div
          v-if="hasImage"
          class="aspect-[16/10] bg-zinc-200 dark:bg-zinc-800"
        >
          <img
            :src="heroImageUrl"
            :alt="String(event.title)"
            class="w-full h-full object-cover"
          >
          <!-- Gradient fade at bottom -->
          <div class="absolute inset-x-0 bottom-0 h-1/3 bg-gradient-to-t from-white dark:from-zinc-950 to-transparent" />
        </div>

        <!-- Gradient header for no image -->
        <div
          v-else
          class="relative h-40 sm:h-48 bg-gradient-to-br from-zinc-900 via-zinc-800 to-zinc-900 overflow-hidden"
        >
          <!-- Subtle radial accent -->
          <div class="absolute inset-0 bg-[radial-gradient(ellipse_100%_100%_at_50%_0%,rgba(189,28,38,0.2),transparent)]" />

          <!-- Grid pattern -->
          <svg class="absolute inset-0 w-full h-full opacity-[0.06]" xmlns="http://www.w3.org/2000/svg">
            <defs>
              <pattern id="mobile-hero-grid" width="40" height="40" patternUnits="userSpaceOnUse">
                <path d="M 40 0 L 0 0 0 40" fill="none" stroke="white" stroke-width="0.5" />
              </pattern>
            </defs>
            <rect width="100%" height="100%" fill="url(#mobile-hero-grid)" />
          </svg>
        </div>
      </div>

      <!-- Content section -->
      <div class="px-6 sm:px-8 -mt-4 relative z-10" :class="{ 'pt-4': !hasImage }">
        <!-- Badges -->
        <div class="flex flex-wrap items-center gap-2 mb-4">
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
            :class="mobileStatusBadgeClasses"
          >
            <span class="relative flex h-2 w-2">
              <span
                v-if="isLive"
                class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75"
                :class="mobileStatusDotClasses"
              />
              <span class="relative inline-flex rounded-full h-2 w-2" :class="mobileStatusDotClasses" />
            </span>
            {{ statusLabel }}
          </span>
        </div>

        <!-- Title -->
        <h1 class="text-2xl sm:text-3xl font-extrabold leading-tight text-zinc-900 dark:text-zinc-100 mb-3">
          {{ event.title }}
        </h1>

        <!-- Date -->
        <div class="flex flex-wrap items-baseline gap-x-2 gap-y-1 mb-6">
          <span class="font-semibold text-zinc-900 dark:text-zinc-100">{{ dateSpan.primary }}</span>
          <span class="text-sm text-zinc-600 dark:text-zinc-400">{{ dateSpan.secondary }}</span>
          <span v-if="relativeLabel" class="text-sm text-zinc-500 dark:text-zinc-500">
            · {{ relativeLabel }}
          </span>
        </div>

        <!-- Action Buttons Slot -->
        <div v-if="$slots.actions" class="pb-2">
          <slot name="actions" :on-image="false" />
        </div>
      </div>
    </div>
  </div>
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

// Get hero image URL - uses main_image_url accessor with fallback
const heroImageUrl = computed(() => props.event.main_image_url || null);

const hasImage = computed(() => !!heroImageUrl.value);

// Responsive height classes - taller hero for impact
const heroContainerClasses = computed(() =>
  hasImage.value
    ? 'min-h-[480px] lg:min-h-[560px] xl:min-h-[640px]'
    : 'min-h-[420px] lg:min-h-[500px] xl:min-h-[560px]',
);

const dateSpan = computed(() =>
  formatEventDateSpan(props.event.date, props.event.end_date, {
    allDay: props.event.is_all_day,
    locale: locale.value,
  }),
);

const statusBadgeClasses = computed(() =>
  isLive.value
    ? 'bg-emerald-500/30 text-emerald-100 border border-emerald-400/40'
    : 'bg-zinc-500/30 text-zinc-200 border border-zinc-400/30',
);

const statusDotClasses = computed(() => (isLive.value ? 'bg-emerald-400' : 'bg-zinc-300'));

const mobileStatusBadgeClasses = computed(() =>
  isLive.value
    ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400'
    : 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400',
);

const mobileStatusDotClasses = computed(() =>
  isLive.value ? 'bg-emerald-500' : 'bg-zinc-400 dark:bg-zinc-500',
);
</script>

<style scoped>
/* Full-bleed hero - breaks out of wrapper grid */
.event-hero-wrapper {
  width: 100vw;
  position: relative;
  left: 50%;
  right: 50%;
  margin-left: -50vw;
  margin-right: -50vw;
}

/* Smooth reduced motion support */
@media (prefers-reduced-motion: reduce) {
  .animate-ping {
    animation: none;
  }
}
</style>
