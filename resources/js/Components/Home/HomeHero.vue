<template>
  <section
    :class="[
      'relative isolate flex flex-col overflow-hidden text-foreground',
      'sm:min-h-40 sm:justify-end sm:bg-background sm:p-7',
    ]"
    data-slot="home-hero"
  >
    <img
      :src="photo.src"
      :alt="photo.alt"
      :style="{ objectPosition: photo.focalPoint }"
      loading="lazy"
      class="absolute inset-0 -z-10 hidden size-full object-cover opacity-65 sm:block"
    >
    <!-- Fade the photo into the page background so light mode stays paper. Phones get only the date and greeting. -->
    <div class="absolute inset-0 -z-10 hidden bg-gradient-to-r from-background/95 from-0% via-background/68 via-32% to-background/22 to-62% sm:block" aria-hidden="true" />
    <div class="absolute inset-0 -z-10 hidden bg-gradient-to-l from-background/95 from-0% via-background/68 via-32% to-transparent to-62% sm:block" aria-hidden="true" />
    <div class="absolute inset-0 -z-10 hidden bg-gradient-to-t from-background/78 via-transparent to-background/18 sm:block" aria-hidden="true" />

    <div class="min-w-0">
      <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-muted-foreground" data-testid="hero-date">
        {{ today }}
      </p>
      <h1 class="u-display mt-2 text-balance text-2xl text-foreground sm:text-3xl" data-tour="greeting-section">
        {{ greeting }}
      </h1>
      <p v-if="summary" class="mt-1 text-sm text-foreground" data-testid="hero-summary">
        {{ summary }}
      </p>
    </div>
  </section>
</template>

<script setup lang="ts">
import { computed } from 'vue';

import type { HomeHeroImage } from './types';

import { useDateFormatter } from '@/Composables/useDateFormatter';
import { communityPhotos } from '@/Constants/communityPhotos';
import { VILNIUS_TIMEZONE } from '@/Utils/dateTime';

const props = defineProps<{
  greeting: string;
  image: HomeHeroImage | null;
  /** One line on what waits, so the answer shows before the rep scrolls. */
  summary?: string | null;
}>();

const { locale } = useDateFormatter();

const DAY_MS = 86_400_000;

const photo = computed(() => {
  if (props.image) {
    return { src: props.image.url, alt: '', focalPoint: props.image.focalPoint ?? '50% 30%' };
  }

  return { ...communityPhotos[Math.floor(Date.now() / DAY_MS) % communityPhotos.length], focalPoint: '50% 30%' };
});

const today = computed(() => new Intl.DateTimeFormat(locale.value, {
  timeZone: VILNIUS_TIMEZONE,
  weekday: 'long',
  month: 'long',
  day: 'numeric',
}).format(new Date()));

</script>
