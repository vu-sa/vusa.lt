<template>
  <template v-for="(element, index) in content" :key="element.id">
    <!-- Wrap async data-fetching components with Suspense -->
    <Suspense v-if="['news', 'calendar'].includes(element.type)"
      :class="[getSpacingClass(element.type, index), isFullBleedType(element.type) ? 'full-bleed' : '']">
      <template #default>
        <component :is="getComponentForType(element.type)" :element="element" :html="html"
          :is-first-element="index === 0" />
      </template>
      <template #fallback>
        <div class="w-full py-8 flex justify-center">
          <div class="animate-pulse flex flex-col gap-2 items-center">
            <div class="h-10 w-10 rounded-full bg-zinc-200 dark:bg-zinc-700" />
            <div class="h-4 w-48 rounded bg-zinc-200 dark:bg-zinc-700" />
          </div>
        </div>
      </template>
    </Suspense>

    <!-- Regular component rendering for non-data fetching components -->
    <component :is="getComponentForType(element.type)" v-else :element="element" :html="html"
      :is-first-element="index === 0"
      :class="[getSpacingClass(element.type, index), isFullBleedType(element.type) ? 'full-bleed' : '']">
      <!-- Default slot for components that need content -->
      <template v-if="['shadcn-card'].includes(element.type)">
        <RichContentTiptapHTML v-if="!html" :json_content="element.json_content" />
        <div v-else v-html="element.html" />
      </template>
    </component>
  </template>
</template>

<script setup lang="ts">
import { computed, defineAsyncComponent, markRaw, shallowRef } from 'vue';


const RichContentTiptapHTML = defineAsyncComponent(() => import('@/Components/RichContentTiptapHTML.vue'));

// Create a component registry for content types
const contentComponents = shallowRef({
  'tiptap': markRaw(defineAsyncComponent(() => import('./RichContent/Types/TiptapDisplay.vue'))),
  'shadcn-card': markRaw(defineAsyncComponent(() => import('@/Components/RichContentCard.vue'))),
  'shadcn-accordion': markRaw(defineAsyncComponent(() => import('@/Components/RichContent/RCAccordion.vue'))),
  'image-grid': markRaw(defineAsyncComponent(() => import('./RichContent/Types/ImageGridDisplay.vue'))),
  'hero': markRaw(defineAsyncComponent(() => import('@/Components/RichContent/RCHeroSection/HeroElement.vue'))),
  'spotify-embed': markRaw(defineAsyncComponent(() => import('@/Components/RichContent/RCSpotifyEmbed.vue'))),
  'number-stat-section': markRaw(defineAsyncComponent(() => import('@/Components/RichContent/RCNumberStatSection/RCNumberSection.vue'))),
  'news': markRaw(defineAsyncComponent(() => import("@/Components/Public/NewsElement.vue"))),
  'calendar': markRaw(defineAsyncComponent(() => import("@/Components/Public/FullWidth/EventCalendarElement.vue"))),
  'flow-graph': markRaw(defineAsyncComponent(() => import('@/Components/RichContent/RCFlowGraph.vue'))),
});

const props = defineProps<{
  content: models.ContentPart[];
  html?: boolean;
}>();

// Get component for type with fallback
function getComponentForType(type: string) {
  return contentComponents.value[type] || contentComponents.value['tiptap'];
}

// Determine spacing class based on element type and position
function getSpacingClass(type: string, index: number): string {
  // No top margin for first element
  const isFirst = index === 0;

  // Using a more structured approach with Tailwind's spacing scale
  // This makes it clearer what the actual spacing values are
  const typeSpacing = {
    'tiptap': 'mb-8', // 2rem bottom
    'shadcn-card': 'mb-12', // 3rem bottom
    'shadcn-accordion': 'mb-12', // 3rem bottom
    'image-grid': 'mb-10', // 2.5rem bottom
    'hero': 'mb-14', // 3.5rem bottom
    'spotify-embed': 'mb-10', // 2.5rem bottom
    'number-stat-section': 'mb-12', // 3rem bottom
    'news': 'mb-12', // 3rem bottom
    'calendar': 'mb-12', // 3rem bottom
    'flow-graph': 'mb-10', // 2.5rem bottom
  };

  // Default spacing if type not defined
  const defaultSpacing = 'mb-8'; // 2rem bottom

  // Apply top margin for all non-first elements
  // Using a more modest top margin to create better visual rhythm
  const topSpacing = isFirst ? '' : 'mt-6'; // 1.5rem top spacing if not first

  return `${topSpacing} ${typeSpacing[type as keyof typeof typeSpacing] || defaultSpacing}`;
}

// Determine if the element type should be full-bleed
function isFullBleedType(type: string): boolean {
  // List of component types that should be full-bleed
  const fullBleedTypes = ['hero', 'calendar', 'news', 'number-stat-section'];
  return fullBleedTypes.includes(type);
}

</script>
