<template>
  <div class="mx-8 mb-4 rounded-lg p-4 lg:mx-16 lg:mb-8 lg:px-8">
    <h2 class="mb-4">
      {{ $t('Taip pat apsilankyk') }}!
    </h2>
    <NCarousel :space-between="30" :loop="true" autoplay :slides-per-view="bannerCount" draggable :interval="2000"
      :show-dots="false">
      <NCarouselItem v-for="banner in banners" :key="banner.id" class="my-auto">
        <a target="_blank" :href="banner.link_url" aria-label="Banner">
          <img :alt="banner.title" class="w-3/4 rounded-xs object-contain lg:max-h-32" :src="banner.image_url">
        </a>
      </NCarouselItem>
    </NCarousel>
  </div>
</template>

<script setup lang="ts">
import { NCarousel, NCarouselItem } from "naive-ui";
import { onBeforeUnmount, ref } from "vue";

defineProps<{
  banners: Array<App.Entities.Banner> | [];
}>();

const calculateBannerCount = (width) => {
  if (width < 768) {
    return 1;
  } else if (width < 992) {
    return 2;
  } else if (width < 1200) {
    return 3;
  } else {
    return 5;
  }
};

const bannerCount = ref(calculateBannerCount(window.innerWidth));

window.addEventListener("resize", () => {
  bannerCount.value = calculateBannerCount(window.innerWidth);
});

onBeforeUnmount(() => {
  window.removeEventListener("resize", () => { });
});
</script>
