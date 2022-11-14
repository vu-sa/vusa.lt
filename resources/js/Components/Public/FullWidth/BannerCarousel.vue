<template>
  <div class="mx-8 mb-4 rounded-lg p-4 lg:mx-16 lg:mb-8 lg:px-8">
    <h2 class="mb-4">
      Dėkojame mūsų studentų festivalio
      <a target="_blank" href="https://www.facebook.com/events/1166674644261425"
        >Studentify</a
      >
      draugams ir rėmėjams:
    </h2>
    <NCarousel
      :space-between="30"
      :loop="true"
      autoplay
      :slides-per-view="bannerCount"
      draggable
      :interval="2000"
      :show-dots="false"
    >
      <NCarouselItem class="my-auto" v-for="banner in banners" :key="banner.id">
        <a target="_blank" :href="banner.link_url">
          <img
            class="rounded-sm shadow-md w-3/4 lg:max-h-32 object-contain"
            :src="banner.image_url"
          />
        </a>
      </NCarouselItem>
    </NCarousel>
  </div>
</template>

<script setup lang="ts">
import { NCarousel, NCarouselItem } from "naive-ui";
import { onBeforeUnmount, ref } from "vue";

defineProps<{
  banners: Array<App.Models.Banner>;
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
  window.removeEventListener("resize", () => {});
});
</script>
