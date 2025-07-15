<template>
  <article :class="[
    'relative flex flex-col',
    styles[layout].container,
    className
  ]">
    <!-- Cover image with optional author credit -->
    <div :class="styles[layout].imageContainer">
      <img 
        :src="article.image" 
        :alt="article.title"
        :class="styles[layout].image"
      >
      <div v-if="article.image_author" :class="styles[layout].imageAuthor">
        <span class="text-xs text-zinc-600 dark:text-zinc-400">{{ article.image_author }}</span>
      </div>
    </div>

    <div :class="styles[layout].content">
      <!-- Meta information: category, date, tenant -->
      <div :class="styles[layout].meta">
        <div class="flex flex-wrap items-center gap-2">
          <div class="flex items-center text-sm text-zinc-500 dark:text-zinc-400">
            <IFluentOrganization16Regular class="mr-1 h-4 w-4" />
            <span>{{ article.tenant }}</span>
          </div>
          <span class="text-zinc-300 dark:text-zinc-600">•</span>
          <div class="flex items-center text-sm text-zinc-500 dark:text-zinc-400">
            <IFluentCalendarLtr16Regular class="mr-1 h-4 w-4" />
            <time :datetime="formatISODate(article.publish_time)">
              {{ formatStaticTime(new Date(article.publish_time), 
                  { year: "numeric", month: "long", day: "numeric" },
                  locale
              ) }}
            </time>
          </div>
        </div>
        
        <!-- Tags/Categories -->
        <div v-if="article.tags?.length > 0" class="mt-2 flex flex-wrap gap-1.5">
          <button 
            v-for="tag in article.tags" 
            :key="tag.id" 
            @click="tag.alias && navigateToTaggedNews(tag.alias)"
            class="cursor-pointer"
          >
            <NButton size="tiny" round class="hover:bg-red-50 dark:hover:bg-red-950 transition-colors">
              {{ typeof tag.name === 'object' ? (tag.name[locale] || tag.name.lt || tag.name.en) : tag.name }}
            </NButton>
          </button>
        </div>
      </div>

      <!-- Title -->
      <h1 :class="styles[layout].title">{{ article.title }}</h1>

      <!-- Other language notice -->
      <div v-if="otherLangURL" :class="styles[layout].otherLang">
        <em class="typography text-sm">
          {{ $t("Puslapis egzistuoja kita kalba") }}!
          <span class="ml-2">
            <SmartLink :href="otherLangURL">
              <NButton tertiary round size="small">{{ $t("Atidaryti") }}.</NButton>
            </SmartLink>
          </span>
        </em>
      </div>

      <!-- Content -->
      <div :class="styles[layout].articleContent">
        <slot />
      </div>
    </div>
  </article>
</template>

<script setup lang="ts">
import { trans as $t } from "laravel-vue-i18n";
import { computed } from "vue";
import { usePage, router } from "@inertiajs/vue3";
import { formatStaticTime } from "@/Utils/IntlTime";
import SmartLink from "@/Components/Public/SmartLink.vue";

// Import icons
import IFluentOrganization16Regular from "~icons/fluent/organization-16-regular";
import IFluentCalendarLtr16Regular from "~icons/fluent/calendar-ltr-16-regular";

// Import NButton for tag buttons
import { NButton } from "naive-ui";

const props = defineProps<{
  article: App.Entities.News;
  otherLangURL?: string;
  layout?: 'modern' | 'classic' | 'immersive';
  locale?: string;
  className?: string;
}>();

// Default to 'modern' layout
const layout = computed(() => props.layout || 'modern');
const locale = computed(() => props.locale || 'lt');

// Formatter for ISO date
function formatISODate(date: string | number) {
  return new Date(date).toISOString();
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

// Predefined styles for different layouts
const styles = {
  // Modern layout: clean with larger typography, focused on readability
  modern: {
    container: 'max-w-3xl mx-auto', // Reduced from max-w-5xl for better readability (~70 chars per line)
    imageContainer: 'relative mb-8 overflow-hidden rounded-xl',
    image: 'w-full h-auto max-h-[600px] object-cover object-center',
    imageAuthor: 'absolute bottom-2 right-2 bg-black/30 px-3 py-1 rounded-full backdrop-blur-sm text-white',
    meta: 'mb-4',
    title: 'text-3xl md:text-4xl font-bold mb-6 leading-tight text-zinc-900 dark:text-zinc-50',
    otherLang: 'mb-6',
    articleContent: 'typography max-w-none'
  },
  // Classic layout: traditional news article look
  classic: {
    container: 'max-w-4xl mx-auto',
    imageContainer: 'relative mb-6',
    image: 'w-full h-auto object-cover',
    imageAuthor: 'mt-1 text-right',
    meta: 'mb-5',
    title: 'text-2xl md:text-3xl font-bold mb-6 text-zinc-900 dark:text-zinc-50',
    otherLang: 'mb-5',
    articleContent: 'typography max-w-none'
  },
  // Immersive layout: focus on imagery with a more magazine-style look
  immersive: {
    container: 'max-w-full',
    imageContainer: 'relative mb-0 h-[50vh] md:h-[70vh]',
    image: 'w-full h-full object-cover',
    imageAuthor: 'absolute bottom-4 right-4 bg-black/30 px-3 py-1 rounded-full backdrop-blur-sm text-white',
    meta: 'mt-8 mb-4 max-w-4xl mx-auto px-4',
    title: 'text-4xl md:text-5xl lg:text-6xl font-bold mb-8 max-w-4xl mx-auto px-4 text-zinc-900 dark:text-zinc-50',
    otherLang: 'mb-6 max-w-4xl mx-auto px-4',
    articleContent: 'typography max-w-4xl mx-auto px-4'
  }
};
</script>