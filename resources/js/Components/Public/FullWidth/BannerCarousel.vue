<template>
  <div class="px-8 my-8">
    <Carousel :opts="{
      align: 'start',
    }" :plugins="[Autoplay({
      delay: 3000,
    })]">
      <CarouselContent class="-ml-1">
        <CarouselItem v-for="banner in banners" :key="banner.id" class="basis-1/3 lg:basis-1/4 xl:basis-1/5 pl-1">
          <a target="_blank" :href="banner.link_url" :aria-label="banner.title">
            <img :alt="banner.title" class="w-3/4 rounded-xs mx-auto object-contain lg:max-h-32"
              :src="banner.image_url">
          </a>
        </CarouselItem>
      </CarouselContent>
    </Carousel>
  </div>
</template>

<script setup lang="ts">
import Carousel from "@/Components/ui/carousel/Carousel.vue";
import CarouselContent from "@/Components/ui/carousel/CarouselContent.vue";
import CarouselItem from "@/Components/ui/carousel/CarouselItem.vue";
import { onBeforeUnmount, ref } from "vue";
import Autoplay from 'embla-carousel-autoplay'

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
