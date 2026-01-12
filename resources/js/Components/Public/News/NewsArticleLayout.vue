<template>
  <article :class="[
    'relative flex flex-col',
    styles[layout]?.container || 'max-w-3xl mx-auto',
    className
  ]">
    <!-- CLASSIC LAYOUT: Two-column grid with image beside title, left-aligned -->
    <template v-if="layout === 'classic'">
      <div class="mb-10 grid gap-10 md:grid-cols-5 md:items-start">
        <!-- Image column (2/5 width on desktop) -->
        <div class="order-2 md:order-1 md:col-span-2">
          <div class="overflow-hidden rounded-xl">
            <img 
              :src="article.image as string" 
              :alt="article.title"
              class="aspect-[4/3] w-full object-cover rounded-xl"
              :style="{ viewTransitionName: `news-image-${article.id}` }"
            >
          </div>
          <div v-if="article.image_author" class="mt-2 text-right text-xs text-muted-foreground">
            {{ article.image_author }}
          </div>
        </div>
        
        <!-- Title column (3/5 width on desktop) - left aligned -->
        <div class="order-1 flex flex-col md:order-2 md:col-span-3">
          <!-- Meta -->
          <div class="mb-3 flex flex-wrap items-center gap-2 text-sm text-muted-foreground">
            <div class="flex items-center">
              <IFluentOrganization16Regular class="mr-1.5 h-4 w-4" />
              <span>{{ article.tenant }}</span>
            </div>
            <span class="text-border">•</span>
            <div v-if="article.publish_time" class="flex items-center">
              <IFluentCalendarLtr16Regular class="mr-1.5 h-4 w-4" />
              <time :datetime="formatISODate(article.publish_time)">
                {{ formatStaticTime(new Date(article.publish_time), 
                    { year: "numeric", month: "long", day: "numeric" },
                    locale === 'lt' ? LocaleEnum.LT : LocaleEnum.EN
                ) }}
              </time>
            </div>
          </div>

          <!-- Tags -->
          <div v-if="article.tags && article.tags.length > 0" class="mb-3 flex flex-wrap gap-1.5">
            <button 
              v-for="tag in article.tags" 
              :key="tag.id" 
              @click="tag.alias && navigateToTaggedNews(tag.alias)"
              class="inline-flex cursor-pointer items-center rounded-full bg-primary/10 px-3 py-1 text-xs font-medium text-primary transition-colors hover:bg-primary/20"
            >
              {{ getTagName(tag) }}
            </button>
          </div>

          <!-- Title -->
          <h1 class="font-heading text-2xl font-bold leading-tight text-foreground md:text-3xl lg:text-4xl">
            {{ article.title }}
          </h1>
        </div>
      </div>

      <!-- Other language notice - below image, subtle with flag -->
      <Link
        v-if="otherLangURL"
        :href="otherLangURL"
        class="group -mt-4 mb-8 inline-flex items-center gap-2 text-sm text-muted-foreground transition-colors hover:text-foreground"
      >
        <img 
          :src="`https://hatscripts.github.io/circle-flags/flags/${locale === 'lt' ? 'gb' : 'lt'}.svg`" 
          width="16" 
          class="rounded-full"
          :alt="locale === 'lt' ? 'English' : 'Lietuviškai'"
        >
        <span>{{ locale === 'lt' ? 'Read in English' : 'Skaityti lietuviškai' }}</span>
        <ArrowRightIcon class="h-3 w-3 transition-transform group-hover:translate-x-0.5" />
      </Link>

      <!-- Highlights callout (integrated in flow) -->
      <HighlightsCallout v-if="article.highlights?.length" :highlights="article.highlights" class="mb-6" />

      <!-- Article content -->
      <div class="typography max-w-none text-base leading-7 text-foreground/90 md:text-lg md:leading-8">
        <slot />
      </div>
    </template>

    <!-- IMMERSIVE LAYOUT: Wider hero with rounded corners and gradient below title -->
    <template v-else-if="layout === 'immersive'">
      <div class="relative mb-10 overflow-hidden rounded-2xl">
        <div class="relative h-[35vh] md:h-[40vh] lg:h-[45vh]">
          <img
            :src="article.image as string"
            :alt="article.title"
            class="h-full w-full object-cover rounded-xl"
            :style="{ viewTransitionName: `news-image-${article.id}` }"
          />
          <!-- Gradient overlay - stronger at bottom below title -->
          <div class="absolute inset-0 bg-gradient-to-t from-black via-black/60 to-transparent" />
          
          <!-- Top bar: image author and language switch -->
          <div class="absolute inset-x-0 top-0 flex items-center justify-between px-4 pt-4 md:px-6">
            <!-- Other language notice - compact badge in top left -->
            <Link
              v-if="otherLangURL"
              :href="otherLangURL"
              class="group flex items-center gap-1.5 rounded-full bg-white px-3 py-1.5 text-xs font-medium text-zinc-800 shadow-md transition-all hover:scale-105 hover:shadow-lg"
            >
              <img 
                :src="`https://hatscripts.github.io/circle-flags/flags/${locale === 'lt' ? 'gb' : 'lt'}.svg`" 
                width="16" 
                class="rounded-full"
                :alt="locale === 'lt' ? 'English' : 'Lietuviškai'"
              >
              <span>{{ locale === 'lt' ? 'EN' : 'LT' }}</span>
            </Link>
            <div v-else />
            
            <!-- Image author on right -->
            <div v-if="article.image_author" class="rounded-full bg-black/40 px-3 py-1 text-xs text-white backdrop-blur-sm">
              {{ article.image_author }}
            </div>
          </div>
          
          <!-- Content overlay -->
          <div class="absolute inset-x-0 bottom-0 px-5 pb-6 md:px-8 md:pb-8">
            <!-- Meta -->
            <div class="mb-2 flex flex-wrap items-center gap-2 text-sm text-white/80">
              <div class="flex items-center">
                <IFluentOrganization16Regular class="mr-1.5 h-4 w-4" />
                <span>{{ article.tenant }}</span>
              </div>
              <span class="text-white/50">•</span>
              <div v-if="article.publish_time" class="flex items-center">
                <IFluentCalendarLtr16Regular class="mr-1.5 h-4 w-4" />
                <time :datetime="formatISODate(article.publish_time)">
                  {{ formatStaticTime(new Date(article.publish_time), 
                      { year: "numeric", month: "long", day: "numeric" },
                      locale === 'lt' ? LocaleEnum.LT : LocaleEnum.EN
                  ) }}
                </time>
              </div>
            </div>

            <!-- Tags -->
            <div v-if="article.tags && article.tags.length > 0" class="mb-2 flex flex-wrap gap-2">
              <button
                v-for="tag in article.tags"
                :key="tag.id"
                @click="tag.alias && navigateToTaggedNews(tag.alias)"
                class="inline-flex cursor-pointer items-center rounded-full bg-white/20 px-3 py-1 text-xs font-medium text-white backdrop-blur-sm transition-colors hover:bg-white/30"
              >
                {{ getTagName(tag) }}
              </button>
            </div>

            <!-- Title -->
            <h1 class="font-heading text-xl font-bold leading-tight text-white drop-shadow-lg md:text-3xl lg:text-4xl">
              {{ article.title }}
            </h1>
          </div>
        </div>
      </div>

      <!-- Highlights callout (integrated in flow) -->
      <div class="mx-auto max-w-3xl px-4">
        <HighlightsCallout v-if="article.highlights?.length" :highlights="article.highlights" class="mb-6" />
      </div>

      <!-- Article content -->
      <div class="typography mx-auto max-w-3xl px-4 text-base leading-7 text-foreground/90 md:text-lg md:leading-8">
        <slot />
      </div>
    </template>

    <!-- MODERN LAYOUT: Default centered design -->
    <template v-else-if="layout === 'modern'">
      <!-- Cover image -->
      <div class="relative mb-8 overflow-hidden rounded-xl">
        <img 
          :src="article.image as string" 
          :alt="article.title"
          class="max-h-[500px] w-full object-cover object-center rounded-xl"
          :style="{ viewTransitionName: `news-image-${article.id}` }"
        >
        <div v-if="article.image_author" class="absolute bottom-3 right-3 rounded-full bg-black/40 px-3 py-1 text-xs text-white backdrop-blur-sm">
          {{ article.image_author }}
        </div>
      </div>

      <!-- Other language notice - below image, subtle with flag -->
      <Link
        v-if="otherLangURL"
        :href="otherLangURL"
        class="group mb-4 inline-flex items-center gap-2 text-sm text-muted-foreground transition-colors hover:text-foreground"
      >
        <img 
          :src="`https://hatscripts.github.io/circle-flags/flags/${locale === 'lt' ? 'gb' : 'lt'}.svg`" 
          width="16" 
          class="rounded-full"
          :alt="locale === 'lt' ? 'English' : 'Lietuviškai'"
        >
        <span>{{ locale === 'lt' ? 'Read in English' : 'Skaityti lietuviškai' }}</span>
        <ArrowRightIcon class="h-3 w-3 transition-transform group-hover:translate-x-0.5" />
      </Link>

      <!-- All meta in one row: org, date, tags -->
      <div class="mb-4 flex flex-wrap items-center gap-x-3 gap-y-2 text-sm">
        <div class="flex items-center text-muted-foreground">
          <IFluentOrganization16Regular class="mr-1.5 h-4 w-4" />
          <span>{{ article.tenant }}</span>
        </div>
        <span class="text-border">•</span>
        <div v-if="article.publish_time" class="flex items-center text-muted-foreground">
          <IFluentCalendarLtr16Regular class="mr-1.5 h-4 w-4" />
          <time :datetime="formatISODate(article.publish_time)">
            {{ formatStaticTime(new Date(article.publish_time), 
                { year: "numeric", month: "long", day: "numeric" },
                locale === 'lt' ? LocaleEnum.LT : LocaleEnum.EN
            ) }}
          </time>
        </div>
        <template v-if="article.tags && article.tags.length > 0">
          <span class="text-border">•</span>
          <button 
            v-for="tag in article.tags" 
            :key="tag.id" 
            @click="tag.alias && navigateToTaggedNews(tag.alias)"
            class="inline-flex cursor-pointer items-center rounded-full bg-primary/10 px-2.5 py-0.5 text-xs font-medium text-primary transition-colors hover:bg-primary/20"
          >
            {{ getTagName(tag) }}
          </button>
        </template>
      </div>

      <!-- Title -->
      <h1 class="font-heading mb-6 text-3xl font-bold leading-tight text-foreground md:text-4xl">
        {{ article.title }}
      </h1>

      <!-- Highlights callout (integrated in flow) -->
      <HighlightsCallout v-if="article.highlights?.length" :highlights="article.highlights" class="mb-6" />

      <!-- Article content -->
      <div class="typography max-w-none text-base leading-7 text-foreground/90 md:text-lg md:leading-8">
        <slot />
      </div>
    </template>

    <!-- HEADLINE LAYOUT: Title above image, left-aligned -->
    <template v-else>
      <!-- All meta in one row: org, date, tags -->
      <div class="mb-3 flex flex-wrap items-center gap-x-3 gap-y-2 text-sm">
        <div class="flex items-center text-muted-foreground">
          <IFluentOrganization16Regular class="mr-1.5 h-4 w-4" />
          <span>{{ article.tenant }}</span>
        </div>
        <span class="text-border">•</span>
        <div v-if="article.publish_time" class="flex items-center text-muted-foreground">
          <IFluentCalendarLtr16Regular class="mr-1.5 h-4 w-4" />
          <time :datetime="formatISODate(article.publish_time)">
            {{ formatStaticTime(new Date(article.publish_time), 
                { year: "numeric", month: "long", day: "numeric" },
                locale === 'lt' ? LocaleEnum.LT : LocaleEnum.EN
            ) }}
          </time>
        </div>
        <template v-if="article.tags && article.tags.length > 0">
          <span class="text-border">•</span>
          <button 
            v-for="tag in article.tags" 
            :key="tag.id" 
            @click="tag.alias && navigateToTaggedNews(tag.alias)"
            class="inline-flex cursor-pointer items-center rounded-full bg-primary/10 px-2.5 py-0.5 text-xs font-medium text-primary transition-colors hover:bg-primary/20"
          >
            {{ getTagName(tag) }}
          </button>
        </template>
      </div>

      <!-- Title - smaller, left-aligned -->
      <h1 class="font-heading mb-5 text-2xl font-bold leading-snug text-foreground md:text-3xl">
        {{ article.title }}
      </h1>

      <!-- Cover image -->
      <div class="relative mb-6 overflow-hidden rounded-xl">
        <img 
          :src="article.image as string" 
          :alt="article.title"
          class="aspect-video w-full object-cover object-center rounded-xl"
          :style="{ viewTransitionName: `news-image-${article.id}` }"
        >
        <div v-if="article.image_author" class="absolute bottom-3 right-3 rounded-full bg-black/40 px-3 py-1 text-xs text-white backdrop-blur-sm">
          {{ article.image_author }}
        </div>
      </div>

      <!-- Other language notice - below image, subtle with flag -->
      <Link
        v-if="otherLangURL"
        :href="otherLangURL"
        class="group mb-5 inline-flex items-center gap-2 text-sm text-muted-foreground transition-colors hover:text-foreground"
      >
        <img 
          :src="`https://hatscripts.github.io/circle-flags/flags/${locale === 'lt' ? 'gb' : 'lt'}.svg`" 
          width="16" 
          class="rounded-full"
          :alt="locale === 'lt' ? 'English' : 'Lietuviškai'"
        >
        <span>{{ locale === 'lt' ? 'Read in English' : 'Skaityti lietuviškai' }}</span>
        <ArrowRightIcon class="h-3 w-3 transition-transform group-hover:translate-x-0.5" />
      </Link>

      <!-- Highlights callout (integrated in flow) -->
      <HighlightsCallout v-if="article.highlights?.length" :highlights="article.highlights" class="mb-6" />

      <!-- Article content - optimized typography -->
      <div class="typography max-w-none text-base leading-7 text-foreground/90 md:text-lg md:leading-8">
        <slot />
      </div>
    </template>
  </article>
</template>

<script setup lang="ts">
import { trans as $t } from "laravel-vue-i18n";
import { computed } from "vue";
import { usePage, router, Link } from "@inertiajs/vue3";
import { ArrowRightIcon } from 'lucide-vue-next';
import { formatStaticTime } from "@/Utils/IntlTime";
import { LocaleEnum } from "@/Types/enums";
import HighlightsCallout from '@/Components/Public/News/HighlightsCallout.vue';

// Import icons
import IFluentOrganization16Regular from "~icons/fluent/organization-16-regular";
import IFluentCalendarLtr16Regular from "~icons/fluent/calendar-ltr-16-regular";

const props = defineProps<{
  article: App.Entities.News;
  otherLangURL?: string;
  layout?: 'modern' | 'classic' | 'immersive' | 'headline';
  locale?: string;
  className?: string;
}>();

// Default to 'modern' layout
const layout = computed(() => props.layout || 'modern');
const locale = computed(() => props.locale || 'lt');

// Formatter for ISO date
function formatISODate(date: string | number | null | undefined) {
  return date ? new Date(date).toISOString() : '';
}

// Get tag name handling both string and translatable object
function getTagName(tag: App.Entities.Tag): string {
  if (!tag.name) return 'Unknown';
  if (typeof tag.name === 'string') return tag.name;
  if (typeof tag.name === 'object' && !Array.isArray(tag.name)) {
    const nameObj = tag.name as Record<string, string>;
    return nameObj[locale.value] || nameObj.lt || nameObj.en || 'Unknown';
  }
  return 'Unknown';
}

// Navigate to tagged news archive
function navigateToTaggedNews(tagAlias: string) {
  router.visit(route('newsArchive', {
    lang: locale.value,
    newsString: locale.value === 'lt' ? 'naujienos' : 'news',
    subdomain: usePage().props.tenant?.subdomain || 'www',
    tag: tagAlias // This will be added as a query parameter
  }));
}

// Container styles for different layouts
const styles: Record<string, { container: string }> = {
  modern: {
    container: 'max-w-3xl mx-auto',
  },
  classic: {
    container: 'max-w-4xl mx-auto',
  },
  immersive: {
    container: 'max-w-full',
  },
  headline: {
    container: 'max-w-3xl', // Left-aligned, not centered
  },
};
</script>