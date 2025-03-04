<template>
  <template v-for="(element, index) in content" :key="element.id">
    <template v-if="element.type === 'tiptap'"
      v-html="html ? element.html : generateHTMLfromTiptap(element.json_content)">
      <RichContentTiptapHTML v-if="!html" :json_content="element.json_content" />
      <div v-else v-html="element.html" />
    </template>
    <!-- Card -->
    <RichContentCard v-else-if="element.type === 'shadcn-card'" class="not-typography mb-4" :element="element">
      <RichContentTiptapHTML v-if="!html" :json_content="element.json_content" />
      <div v-else v-html="element.html" />
    </RichContentCard>
    <!-- Accordion -->
    <RichContentAccordion v-else-if="element.type === 'shadcn-accordion'" :element :html />
    <!-- Image Grid -->
    <div v-else-if="element.type === 'image-grid'" class="space-y-4">
      <NImageGroup :show-toolbar="false">
        <div class="grid grid-flow-row-dense grid-cols-6 gap-4">
          <div v-for="(image, index) in element.json_content" :key="index" :class="getClassesForImage(image.colspan)">
            <NImage :src="image.image" width="100%" class="size-full rounded-md shadow-xs" object-fit="cover" />
          </div>
        </div>
      </NImageGroup>
    </div>
    <!-- Hero -->
    <HeroElement v-else-if="element.type === 'hero'" :is-first-element="index === 0" :element="element" />
    <!-- Spotify Embed -->
    <RCSpotifyEmbed v-else-if="element.type === 'spotify-embed'" :element />
    <!-- News -->
    <Suspense v-else-if="element.type === 'news'" class="mx-auto mt-8">
      <NewsElement :element />
    </Suspense>

    <Suspense v-else-if="element.type === 'calendar'" class="mx-auto mt-8">
      <EventCalendar :element />
    </Suspense>
  </template>
</template>

<script setup lang="ts">
import { defineAsyncComponent } from 'vue';

const RCSpotifyEmbed = defineAsyncComponent(() => import('@/Components/RichContent/RCSpotifyEmbed.vue'));

const HeroElement = defineAsyncComponent(() => import('@/Components/RichContent/RCHeroSection/HeroElement.vue'));

const RichContentCard = defineAsyncComponent(() => import('@/Components/RichContentCard.vue'));

const RichContentAccordion = defineAsyncComponent(() => import('@/Components/RichContentAccordion.vue'));

const RichContentTiptapHTML = defineAsyncComponent(() => import('@/Components/RichContentTiptapHTML.vue'));

const EventCalendar = defineAsyncComponent(() => import("@/Components/Public/FullWidth/EventCalendarElement.vue"));

const NewsElement = defineAsyncComponent(() => import("@/Components/Public/NewsElement.vue"));

const props = defineProps<{
  content: models.ContentPart[];
  html?: boolean;
}>();

const getClassesForImage = (colspan: string) => {
  if (colspan === 'col-span-full') {
    return `h-48 md:h-60 ${colspan}`;
  }

  return `md:h-40 ${colspan}`;
};

</script>

<style>
.not-typography {
  p {
    margin-top: 0.5rem;

    &:last-child {
      margin-bottom: 0;
    }
  }
}
</style>
