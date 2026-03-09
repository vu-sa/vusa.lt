<template>
  <div v-if="loading" class="py-4 px-4 md:px-8 lg:px-12">
    <div class="w-full max-w-7xl mx-auto">
      <div class="grid gap-6 lg:gap-8 grid-cols-1 lg:grid-cols-[2fr_1fr]">
        <div class="space-y-4">
          <Skeleton class="aspect-video w-full rounded-lg" />
          <Skeleton class="h-4 w-32" />
          <Skeleton class="h-8 w-3/4" />
          <Skeleton class="h-20 w-full" />
        </div>
        <div class="space-y-3">
          <Skeleton class="h-6 w-24" />
          <div v-for="i in 4" :key="i" class="flex items-center gap-3 py-2">
            <Skeleton class="w-16 h-12 rounded flex-shrink-0" />
            <div class="flex-1 space-y-2">
              <Skeleton class="h-3 w-full" />
              <Skeleton class="h-2 w-20" />
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div v-else-if="error" class="py-4 px-4 md:px-8 lg:px-12 text-red-500" role="alert">
    <div class="max-w-7xl mx-auto">
      {{ $t("Nepavyko užkrauti naujienų") }}
    </div>
  </div>
  <section v-else-if="newsItems && newsItems.length > 0" class="py-4 px-4 md:px-8 lg:px-12" aria-labelledby="news-section-heading">
    <h2 id="news-section-heading" class="sr-only">{{ $t("accessibility.news_and_announcements") }}</h2>
    <div class="max-w-7xl mx-auto grid gap-6 lg:gap-8 grid-cols-1 lg:grid-cols-[2fr_1fr]">
      <!-- Main Carousel -->
      <section class="relative" aria-labelledby="featured-news-heading">
        <h3 id="featured-news-heading" class="sr-only">{{ $t("accessibility.featured_news") }}</h3>
        <Carousel v-if="newsItems.length" class="w-full" :opts="{ loop: true, skipSnaps: false }" :plugins="carouselPlugins"
          @init-api="onCarouselInit" role="region" aria-label="Featured news carousel">
          <CarouselContent>
            <CarouselItem v-for="(item, itemIndex) in newsItems" :key="item.id">
              <div class="flex flex-col">
                <SmartLink :href="getNewsRoute(item)" prefetch>
                  <div class="overflow-hidden rounded-xl aspect-video group/img">
                    <img :src="getImageSrc(item.image)" :alt="item.title"
                      class="w-full h-full object-cover rounded-xl transition-all duration-300 group-hover/img:brightness-105 group-hover/img:contrast-[1.02]" 
                      width="800"
                      height="450"
                      :loading="itemIndex === 0 ? 'eager' : 'lazy'"
                      :fetchpriority="itemIndex === 0 ? 'high' : undefined"
                      :style="currentSlide === itemIndex ? { viewTransitionName: `news-image-${item.id}` } : {}">
                  </div>
                </SmartLink>
                <p v-if="item.publish_time" class="text-zinc-500 dark:text-zinc-400 mt-3 text-sm sm:text-base">
                  {{ formatStaticTime(new Date(item.publish_time), { year: "numeric", month: "long", day: "numeric" },
                    $page.props.app.locale) }}
                </p>
                <SmartLink :href="getNewsRoute(item)" prefetch>
                  <h2
                    class="font-heading mt-2 font-bold text-lg sm:text-xl lg:text-2xl leading-tight text-zinc-800 line-clamp-2 dark:text-zinc-50 hover:text-vusa-red transition-colors">
                    {{ item.title }}
                  </h2>
                </SmartLink>
                <div class="leading-relaxed mt-3 text-zinc-600 dark:text-zinc-400 line-clamp-3 text-sm sm:text-base">
                  <div v-html="item.short" />
                </div>
              </div>
            </CarouselItem>
          </CarouselContent>
        </Carousel>
      </section>

      <!-- Sidebar News List -->
      <aside class="flex flex-col gap-4" aria-labelledby="latest-news-heading">
        <h3 id="latest-news-heading" class="text-lg sm:text-xl font-bold text-zinc-900 dark:text-zinc-100">
          {{ $t("Naujausios") }}
        </h3>

        <!-- Mobile horizontal scrolling list using ScrollArea -->
        <div class="lg:hidden -mx-4 md:-mx-8">
          <ScrollArea class="w-full" orientation="horizontal">
            <div class="flex gap-3 pb-4 pt-1 pl-5 pr-4 md:pl-9 md:pr-8">
              <button v-for="(item, index) in newsItems" :key="`mobile-${item.id}`"
                class="flex-shrink-0 w-44 overflow-hidden rounded-lg bg-white dark:bg-zinc-800/50 shadow-sm transition-all hover:shadow-md"
                :class="{
                  'ring-2 ring-vusa-red ring-offset-2 ring-offset-zinc-50 dark:ring-offset-zinc-900': currentSlide === index
                }" :aria-label="`${$t('Rodyti naujieną')}: ${item.title}`" @click="selectSlide(index)">
                <div class="aspect-video overflow-hidden">
                  <img :src="getImageSrc(item.image)" :alt="item.title" loading="lazy" class="w-full h-full object-cover" width="176"
                    height="99">
                </div>
                <div class="p-2.5">
                  <p class="text-zinc-800 dark:text-zinc-200 font-semibold text-xs leading-tight line-clamp-2"
                    :class="{ 'text-vusa-red': currentSlide === index }">
                    {{ item.title }}
                  </p>
                  <p v-if="item.publish_time" class="text-zinc-500 dark:text-zinc-400 text-xs mt-1">
                    {{ formatStaticTime(new Date(item.publish_time), { month: "short", day: "numeric" },
                      $page.props.app.locale) }}
                  </p>
                </div>
              </button>
            </div>
          </ScrollArea>
        </div>

        <!-- Desktop vertical list -->
        <div class="hidden lg:flex flex-col gap-1.5">
          <SmartLink v-for="(item, index) in newsItems" :key="`desktop-${item.id}`" :href="getNewsRoute(item)"
            class="flex items-center gap-3 py-2 px-2 rounded-lg transition-colors cursor-pointer relative hover:bg-zinc-100 dark:hover:bg-zinc-800/50"
            :class="{ 'bg-zinc-100 dark:bg-zinc-800/50': currentSlide === index }"
            :aria-current="currentSlide === index ? 'true' : 'false'" @click.prevent="selectSlide(index)">
            <div v-if="currentSlide === index" class="absolute left-0 top-2 bottom-2 w-0.5 bg-vusa-red rounded-full" style="width: 3px;"></div>
            <div class="ml-2 overflow-hidden rounded-md aspect-[4/3] flex-shrink-0" style="width: 80px;">
              <img :src="getImageSrc(item.image)" :alt="item.title" loading="lazy" class="w-full h-full object-cover" width="80"
                height="60">
            </div>
            <div class="flex flex-col flex-1 min-w-0">
              <span class="text-zinc-800 dark:text-zinc-200 font-semibold text-sm leading-tight line-clamp-2 transition-colors"
                :class="{ 'text-vusa-red': currentSlide === index }">
                {{ item.title }}
              </span>
              <span v-if="item.publish_time" class="text-zinc-500 dark:text-zinc-400 text-xs mt-1.5">
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
        })" prefetch class="inline-flex items-center gap-1.5 font-bold mt-2 text-zinc-900 dark:text-zinc-100 hover:text-vusa-red transition-colors">
          <span>{{ $t("Žiūrėti visas") }}</span>
          <IFluentArrowRight16Regular />
        </SmartLink>
      </aside>
    </div>
  </section>
  <div v-else class="py-4 px-4 md:px-8 lg:px-12 text-center text-zinc-500 dark:text-zinc-400">
    <div class="max-w-7xl mx-auto">
      {{ $t("Nėra naujienų") }}
    </div>
  </div>
</template>

<script setup lang="ts">
import { trans as $t } from "laravel-vue-i18n";
import { ref, computed, onUnmounted } from "vue";
import { usePage } from "@inertiajs/vue3";
import Autoplay from "embla-carousel-autoplay";
import Fade from "embla-carousel-fade";

import SmartLink from "./SmartLink.vue";
import type { News, NewsItem } from '@/Types/contentParts';
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

// Fallback image for news without images
const FALLBACK_IMAGE = '/images/icons/naujienu_foto.png';

// Helper to get image with fallback
const getImageSrc = (image: string | null): string => image ?? FALLBACK_IMAGE;

// Props - element is from content parts, prefetchedNews is from server-side rendering
const props = defineProps<{
  element: News;
  prefetchedNews?: NewsItem[];
}>();

const page = usePage();

// Use prefetched news if available (from home page), otherwise fall back to API fetch
const hasPrefetchedNews = computed(() => props.prefetchedNews && props.prefetchedNews.length > 0);

// Only use API fetch if no prefetched news is available (prevents waterfall on home page)
const { news: apiFetchedNews, loading: apiLoading, error: apiError } = hasPrefetchedNews.value 
  ? { news: ref([]), loading: ref(false), error: ref(null) } 
  : useNewsFetch();

// Combine sources: prefer prefetched data, fall back to API data
const newsItems = computed<NewsItem[]>(() => {
  if (hasPrefetchedNews.value) {
    return props.prefetchedNews as NewsItem[];
  }
  return apiFetchedNews.value as NewsItem[] ?? [];
});

// Loading state: only show loading when using API fetch and it's loading
const loading = computed(() => !hasPrefetchedNews.value && apiLoading.value);

// Error state: only show error when using API fetch and there's an error
const error = computed(() => !hasPrefetchedNews.value && apiError.value);

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
  if (!api) return;
  
  carouselApi.value = api;

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
  // Embla carousel cleans up listeners automatically when destroyed
  carouselApi.value = null;
});
</script>
