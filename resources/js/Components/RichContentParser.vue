<template>
  <template v-for="(element, index) in content" :key="element.id">
    <!-- Async components with Suspense - wrapped in div for proper spacing classes -->
    <!-- Note: Suspense doesn't pass through class attributes, so we wrap in a div -->
    <div v-if="isAsyncComponent(element.type)"
      :class="[getSpacingClass(element.type, index), isFullBleedType(element.type) ? 'full-bleed' : '']">
      <Suspense>
        <template #default>
          <component :is="getComponentForType(element.type)" :element="element" :html="html"
            :is-first-element="index === 0" />
        </template>
        <template #fallback>
          <LoadingSkeleton />
        </template>
      </Suspense>
    </div>

    <!-- Synchronous component rendering (tiptap only) -->
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

// Import commonly used components synchronously for better performance
import TiptapDisplay from './RichContent/Types/TiptapDisplay.vue';

const RichContentTiptapHTML = defineAsyncComponent(() => import('@/Components/RichContentTiptapHTML.vue'));

// Optimize component registry - simplified async component loading
const contentComponents = shallowRef({
  'tiptap': markRaw(TiptapDisplay), // Most common component - load synchronously
  'shadcn-card': markRaw(defineAsyncComponent(() => import('@/Components/RichContentCard.vue'))),
  'shadcn-accordion': markRaw(defineAsyncComponent(() => import('@/Components/RichContent/RCAccordion.vue'))),
  'image-grid': markRaw(defineAsyncComponent(() => import('./RichContent/Types/ImageGridDisplay.vue'))),
  'hero': markRaw(defineAsyncComponent(() => import('@/Components/RichContent/RCHeroSection/HeroElement.vue'))),
  'spotify-embed': markRaw(defineAsyncComponent(() => import('@/Components/RichContent/RCSpotifyEmbed.vue'))),
  'social-embed': markRaw(defineAsyncComponent(() => import('@/Components/RichContent/RCSocialEmbed.vue'))),
  'number-stat-section': markRaw(defineAsyncComponent(() => import('@/Components/RichContent/RCNumberStatSection/RCNumberSection.vue'))),
  'news': markRaw(defineAsyncComponent(() => import("@/Components/Public/NewsElement.vue"))),
  'calendar': markRaw(defineAsyncComponent(() => import("@/Components/Public/FullWidth/EventCalendarElement.vue"))),
  'flow-graph': markRaw(defineAsyncComponent(() => import('@/Components/RichContent/RCFlowGraph.vue'))),
  'content-grid': markRaw(defineAsyncComponent(() => import('./RichContent/Types/ContentGridDisplay.vue'))),
});

const props = defineProps<{
  content: models.ContentPart[];
  html?: boolean;
  class?: string;
}>();

// Get component for type with fallback
function getComponentForType(type: string) {
  return contentComponents.value[type as keyof typeof contentComponents.value] || contentComponents.value['tiptap'];
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
    'social-embed': 'mb-10', // 2.5rem bottom
    'number-stat-section': 'mb-12', // 3rem bottom
    'news': 'mb-12', // 3rem bottom
    'calendar': 'mb-12', // 3rem bottom
    'flow-graph': 'mb-10', // 2.5rem bottom
    'content-grid': 'mb-10', // 2.5rem bottom
  };

  // Default spacing if type not defined
  const defaultSpacing = 'mb-8'; // 2rem bottom

  // Apply top margin for all non-first elements including hero elements
  let topSpacing = '';
  if (!isFirst) {
    // Special handling for hero elements - they need proper spacing when not first
    topSpacing = type === 'hero' ? 'mt-8' : 'mt-6'; // More spacing for hero (2rem) vs regular elements (1.5rem)
  }

  return `${topSpacing} ${typeSpacing[type as keyof typeof typeSpacing] || defaultSpacing}`;
}

// Determine if the element type should be full-bleed
function isFullBleedType(type: string): boolean {
  // List of component types that should be full-bleed
  const fullBleedTypes = ['hero', 'calendar', 'news', 'number-stat-section'];
  return fullBleedTypes.includes(type);
}

// Check if component is async and needs Suspense boundary
function isAsyncComponent(type: string): boolean {
  // Only tiptap is synchronous, all others are async
  return type !== 'tiptap';
}


// Use existing Skeleton component for consistency
import { Skeleton } from '@/Components/ui/skeleton';

const LoadingSkeleton = {
  components: { Skeleton },
  template: `
    <div class="w-full flex items-center justify-center py-8">
      <div class="flex flex-col items-center gap-4">
        <Skeleton class="h-10 w-10 rounded-full" />
        <div class="space-y-2">
          <Skeleton class="h-3 w-32" />
          <Skeleton class="h-2 w-24" />
        </div>
      </div>
    </div>
  `
};

</script>
