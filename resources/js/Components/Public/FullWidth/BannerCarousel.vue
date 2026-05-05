<template>
  <section class="px-8 my-8" aria-label="Partner banners">
    <h2 class="sr-only">
      {{ $t('accessibility.partner_organizations') }}
    </h2>
    <Carousel :opts="{
      align: 'start',
    }" :plugins="[Autoplay({
      delay: 3000,
    })]">
      <CarouselContent class="-ml-1">
        <CarouselItem v-for="banner in banners" :key="banner.id" class="basis-1/3 lg:basis-1/4 xl:basis-1/5 pl-1">
          <a target="_blank" :href="banner.link_url" :aria-label="`${$t('accessibility.visit')} ${banner.title}`">
            <img :alt="banner.title" class="w-3/4 rounded-xs mx-auto object-contain lg:max-h-32"
              :src="banner.image_url" loading="lazy">
          </a>
        </CarouselItem>
      </CarouselContent>
    </Carousel>
  </section>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { useWindowSize } from '@vueuse/core';
import { computed } from 'vue';
import Autoplay from 'embla-carousel-autoplay';

import Carousel from '@/Components/ui/carousel/Carousel.vue';
import CarouselContent from '@/Components/ui/carousel/CarouselContent.vue';
import CarouselItem from '@/Components/ui/carousel/CarouselItem.vue';

defineProps<{
  banners: Array<App.Entities.Banner> | [];
}>();

// Use VueUse composable for efficient window size tracking with automatic cleanup
const { width } = useWindowSize();

// Computed property for reactive banner count based on window width
const bannerCount = computed(() => {
  if (width.value < 768) {
    return 1;
  }
  else if (width.value < 992) {
    return 2;
  }
  else if (width.value < 1200) {
    return 3;
  }
  else {
    return 5;
  }
});
</script>
