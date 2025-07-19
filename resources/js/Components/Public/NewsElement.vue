<template>
  <div v-if="loading" class="my-4 rounded-lg py-4">
    <div class="w-full flex flex-col gap-4">
      <div class="animate-pulse flex flex-col gap-3">
        <Skeleton class="h-64 w-full" />
        <Skeleton class="h-5 w-32" />
        <Skeleton class="h-8 w-3/4" />
        <Skeleton class="h-24 w-full" />
      </div>
    </div>
  </div>
  <div v-else-if="error" class="my-4 rounded-lg py-4 text-red-500" role="alert">
    {{ $t("Nepavyko užkrauti naujienų") }}
  </div>
  <div v-else-if="news && news.length > 0" class="my-4 rounded-lg py-4">
    <section class="grid gap-4 sm:gap-6 grid-cols-1 lg:grid-cols-[2fr_1fr]">
      <!-- Main Carousel -->
      <div class="relative">
        <Carousel v-if="news.length" class="w-full" :opts="{ loop: true, skipSnaps: false }" :plugins="carouselPlugins"
          @init-api="onCarouselInit">
          <CarouselContent>
            <CarouselItem v-for="item in news" :key="item.id">
              <div class="flex flex-col">
                <SmartLink :href="getNewsRoute(item)">
                  <div class="overflow-hidden rounded-md aspect-video">
                    <img :src="item.image" :alt="item.title"
                      class="w-full h-full object-cover hover:scale-105 transition-transform duration-300" width="800"
                      height="450">
                  </div>
                </SmartLink>
                <p class="text-zinc-500 dark:text-zinc-400 mt-2 text-sm sm:text-base">
                  {{ formatStaticTime(new Date(item.publish_time), { year: "numeric", month: "long", day: "numeric" },
                    $page.props.app.locale) }}
                </p>
                <SmartLink :href="getNewsRoute(item)">
                  <h2
                    class="mt-2 font-extrabold text-xl sm:text-2xl leading-tight text-zinc-800 line-clamp-2 dark:text-zinc-50 hover:text-vusa-red">
                    {{ item.title }}
                  </h2>
                </SmartLink>
                <div class="leading-tight mt-2 text-zinc-700 dark:text-zinc-300 line-clamp-3 text-sm sm:text-base">
                  <div v-html="item.short" />
                </div>
              </div>
            </CarouselItem>
          </CarouselContent>
        </Carousel>
      </div>

      <!-- Sidebar News List -->
      <div class="flex flex-col gap-3">
        <h3 class="text-xl sm:text-2xl font-bold text-zinc-900 dark:text-zinc-100">
          {{ $t("Naujausios") }}
        </h3>

        <!-- Mobile horizontal scrolling list using ScrollArea -->
        <div class="md:hidden">
          <ScrollArea class="w-full" orientation="horizontal">
            <div class="flex px-2 pb-4 pt-2">
              <button v-for="(item, index) in news" :key="`mobile-${item.id}`"
                class="flex-shrink-0 w-48 overflow-hidden rounded-md bg-zinc-50 dark:bg-zinc-900 shadow-sm mx-1.5 transition-all"
                :class="{
                  'ring-2 ring-vusa-red ring-offset-2 ring-offset-white dark:ring-offset-zinc-950': currentSlide === index
                }" :aria-label="$t('Rodyti naujieną: {0}', [item.title])" @click="selectSlide(index)">
                <div class="aspect-video overflow-hidden">
                  <img :src="item.image" :alt="item.title" loading="lazy" class="w-full h-full object-cover" width="192"
                    height="108">
                </div>
                <div class="p-3">
                  <p class="text-zinc-800 dark:text-zinc-200 font-semibold text-sm leading-tight line-clamp-2"
                    :class="{ 'text-vusa-red': currentSlide === index }">
                    {{ item.title }}
                  </p>
                  <p class="text-zinc-500 dark:text-zinc-400 text-xs mt-1">
                    {{ formatStaticTime(new Date(item.publish_time), { month: "short", day: "numeric" },
                      $page.props.app.locale) }}
                  </p>
                </div>
              </button>
            </div>
          </ScrollArea>
        </div>

        <!-- Desktop vertical list -->
        <div class="hidden md:flex flex-col gap-1">
          <SmartLink v-for="(item, index) in news" :key="`desktop-${item.id}`" :href="getNewsRoute(item)"
            class="flex items-center gap-2 py-1.5 pl-2 pr-3 rounded-md transition-colors cursor-pointer"
            :class="{ 'bg-zinc-100 dark:bg-zinc-800 border-l-4 border-vusa-red': currentSlide === index }"
            :aria-current="currentSlide === index ? 'true' : 'false'" @click.prevent="selectSlide(index)">
            <div class="overflow-hidden rounded aspect-[4/3] flex-shrink-0" style="width: 70px;">
              <img :src="item.image" :alt="item.title" loading="lazy" class="w-full h-full object-cover" width="70"
                height="53">
            </div>
            <div class="flex flex-col">
              <span class="text-zinc-800 dark:text-zinc-200 font-semibold text-sm leading-tight line-clamp-2"
                :class="{ 'text-vusa-red': currentSlide === index }">
                {{ item.title }}
              </span>
              <span class="text-zinc-500 dark:text-zinc-400 text-xs mt-1">
                {{ formatStaticTime(new Date(item.publish_time), { year: "numeric", month: "long", day: "numeric" },
                  $page.props.app.locale) }}
              </span>
            </div>
          </SmartLink>
        </div>

        <SmartLink :href="route('newsArchive', {
          subdomain: $page.props.tenant?.subdomain ?? 'www',
          lang: $page.props.app.locale === 'lt' ? 'lt' : 'en',
          newsString: $page.props.app.locale === 'lt' ? 'naujienos' : 'news',
        })" class="inline-flex items-center gap-1 font-bold mt-1">
          <span class="text-zinc-900 dark:text-zinc-100">{{ $t("Žiūrėti visas") }}</span>
          <IFluentArrowRight16Regular />
        </SmartLink>
      </div>
    </section>
  </div>
  <div v-else class="my-4 rounded-lg py-4 text-center text-zinc-500 dark:text-zinc-400">
    {{ $t("Nėra naujienų") }}
  </div>
</template>

<script setup lang="ts">
import { trans as $t } from "laravel-vue-i18n";
import { ref, onUnmounted } from "vue";
import { usePage } from "@inertiajs/vue3";
import Autoplay from "embla-carousel-autoplay";
import Fade from "embla-carousel-fade";

import SmartLink from "./SmartLink.vue";
import type { News } from '@/Types/contentParts';
import { formatStaticTime } from "@/Utils/IntlTime";
import { useNewsFetch } from "@/Services/ContentService";
import {
  Carousel,
  CarouselContent,
  CarouselItem,
  type CarouselApi
} from "@/Components/ui/carousel";
import { ScrollArea } from "@/Components/ui/scroll-area";
import { Skeleton } from "@/Components/ui/skeleton";

// Prop definition with proper typing
defineProps<{
  element: News;
}>();

// Use our simplified fetch function from ContentService for better performance
const { news, loading, error } = useNewsFetch();
const page = usePage();

// Carousel state
const carouselApi = ref<CarouselApi | null>(null);
const currentSlide = ref(0);
const autoplayApi = ref<any>(null);

// Plugin options - configured for better user experience
const autoplayOptions = {
  delay: 5000,
  stopOnInteraction: false,
  stopOnMouseEnter: true,
  rootNode: (emblaRoot: HTMLElement) => emblaRoot
};

// Combined plugins for performance optimization
const carouselPlugins = [
  Autoplay(autoplayOptions),
  Fade()
];

// Helper function to create news route
const getNewsRoute = (item: any) => {
  return route('news', {
    lang: item.lang,
    news: item.permalink ?? '',
    newsString: 'naujiena',
    subdomain: page.props.tenant?.subdomain ?? 'www',
  });
};

// Manually select a slide - improved for accessibility
const selectSlide = (index: number) => {
  if (!carouselApi.value) return;
  carouselApi.value.scrollTo(index);
};

// Carousel initialization with proper event handling
const onCarouselInit = (api: CarouselApi) => {
  carouselApi.value = api;

  // if (!api) return;

  // Get autoplay plugin API
  const pluginApis = api.plugins();
  if (pluginApis && pluginApis.autoplay) {
    autoplayApi.value = pluginApis.autoplay;
  }

  // Set initial slide
  currentSlide.value = api.selectedScrollSnap();

  // Listen for slide changes
  api.on("select", () => {
    currentSlide.value = api.selectedScrollSnap();
  });
};

// Clean up event listeners on component unmount for better performance
onUnmounted(() => {
  if (carouselApi.value) {
    carouselApi.value.off("select");
  }
});
</script>
