<template>
  <!-- Edge to edge across the scroll area (100cqw of the shell's container), straight under the
       chrome; the text keeps the page measure. -->
  <section
    class="relative isolate ml-[calc(50%-50cqw)] flex min-h-44 w-[100cqw] flex-col overflow-hidden bg-background text-foreground -mt-4 sm:min-h-52 md:-mt-6"
    data-slot="home-hero"
  >
    <img
      :src="photo.src"
      :srcset="photo.srcset ?? undefined"
      sizes="100vw"
      :alt="photo.alt"
      :style="{ objectPosition: photo.focalPoint }"
      loading="lazy"
      class="absolute inset-0 -z-10 size-full object-cover opacity-65"
    >
    <!-- Phones: faded up from the bottom so the greeting reads over any photo. -->
    <div class="absolute inset-0 -z-10 bg-gradient-to-t from-background via-background/70 to-background/15 sm:hidden" aria-hidden="true" />
    <!-- Wider screens fade the photo into the page from both sides and the bottom, so light mode stays paper. -->
    <div class="absolute inset-0 -z-10 hidden bg-gradient-to-r from-background/95 from-0% via-background/68 via-32% to-background/22 to-62% sm:block" aria-hidden="true" />
    <div class="absolute inset-0 -z-10 hidden bg-gradient-to-l from-background/95 from-0% via-background/68 via-32% to-transparent to-62% sm:block" aria-hidden="true" />
    <div class="absolute inset-0 -z-10 hidden bg-gradient-to-t from-background from-0% via-transparent via-60% to-background/18 sm:block" aria-hidden="true" />

    <div class="relative mx-auto flex w-full max-w-7xl flex-1 flex-col justify-end px-4 pt-8 pb-5 sm:px-6 sm:pb-7 lg:px-8">
      <!-- The right side of the band, for something the page stands there (the winter tree). -->
      <!-- z-2: above the seasonal snowdrift (z-1), so the tree stands on the snow. -->
      <div v-if="$slots.aside" class="absolute inset-y-0 right-4 z-2 hidden pt-3 sm:right-6 md:block lg:right-8" data-slot="home-hero-aside">
        <slot name="aside" />
      </div>

      <div class="min-w-0">
        <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
          <p class="text-[11px] font-semibold uppercase tracking-[0.06em] text-muted-foreground" data-testid="hero-date">
            {{ today }}
          </p>
          <!-- Beside the date, for a line about the day (the season's weather). -->
          <slot name="meta" />
        </div>
        <h1 class="u-display mt-2 text-balance text-2xl text-foreground sm:text-3xl" data-tour="greeting-section">
          {{ greeting }}
        </h1>
      </div>
    </div>
  </section>
</template>

<script setup lang="ts">
import { computed } from 'vue';

import { useDateFormatter } from '@/Composables/useDateFormatter';
import { communityPhotos } from '@/Constants/communityPhotos';
import { VILNIUS_TIMEZONE } from '@/Utils/dateTime';

import type { HomeHeroImage } from './types';

const props = defineProps<{
  greeting: string;
  image: HomeHeroImage | null;
}>();

const { locale } = useDateFormatter();

const DAY_MS = 86_400_000;

const photo = computed(() => {
  if (props.image) {
    return { src: props.image.url, srcset: props.image.srcset, alt: '', focalPoint: props.image.focalPoint ?? '50% 30%' };
  }

  return { ...communityPhotos[Math.floor(Date.now() / DAY_MS) % communityPhotos.length], srcset: null, focalPoint: '50% 30%' };
});

const today = computed(() => new Intl.DateTimeFormat(locale.value, {
  timeZone: VILNIUS_TIMEZONE,
  weekday: 'long',
  month: 'long',
  day: 'numeric',
}).format(new Date()));

</script>
