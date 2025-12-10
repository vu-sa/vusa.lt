<template>
  <template v-for="(element, index) in content" :key="element.id">
    <!-- Async components with Suspense - wrapped in div for proper spacing classes -->
    <!-- Note: Suspense doesn't pass through class attributes, so we wrap in a div -->
    <!-- Using content-aware skeletons to prevent layout shift (CLS) -->
    <div v-if="isAsyncComponent(element.type)"
      :class="[getSpacingClass(element.type, index), isFullBleedType(element.type) ? 'full-bleed' : '', getSkeletonForType(element.type).height]">
      <Suspense>
        <template #default>
          <component :is="getComponentForType(element.type)" :element="element" :html="html"
            :is-first-element="index === 0" />
        </template>
        <template #fallback>
          <component :is="getSkeletonComponent(element.type)" />
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

// Get skeleton dimensions based on content type to prevent layout shift
function getSkeletonForType(type: string): { height: string; template: string } {
  const skeletonConfigs: Record<string, { height: string; template: string }> = {
    'hero': {
      height: 'min-h-[45rem]',
      template: `
        <div class="w-full min-h-[45rem] bg-zinc-200 dark:bg-zinc-800 animate-pulse flex items-end justify-center pb-16">
          <div class="flex flex-col items-center gap-4 max-w-2xl px-8">
            <Skeleton class="h-12 w-96 max-w-full" />
            <Skeleton class="h-6 w-64 max-w-full" />
            <Skeleton class="h-12 w-40 rounded-full mt-4" />
          </div>
        </div>
      `
    },
    'news': {
      height: 'min-h-[400px]',
      template: `
        <div class="w-full py-4">
          <div class="grid gap-4 sm:gap-6 grid-cols-1 lg:grid-cols-[2fr_1fr] px-4 md:px-8">
            <div class="space-y-4">
              <Skeleton class="aspect-video w-full rounded-md" />
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
      `
    },
    'calendar': {
      height: 'min-h-[500px]',
      template: `
        <div class="w-full py-8 px-4 md:px-8">
          <Skeleton class="h-8 w-48 mb-6" />
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div v-for="i in 6" :key="i" class="space-y-3 p-4 border border-zinc-200 dark:border-zinc-700 rounded-lg">
              <Skeleton class="h-4 w-24" />
              <Skeleton class="h-6 w-full" />
              <Skeleton class="h-3 w-3/4" />
            </div>
          </div>
        </div>
      `
    },
    'number-stat-section': {
      height: 'min-h-[200px]',
      template: `
        <div class="w-full py-12 px-4 md:px-8">
          <div class="grid grid-cols-2 md:grid-cols-4 gap-8">
            <div v-for="i in 4" :key="i" class="flex flex-col items-center gap-2">
              <Skeleton class="h-16 w-24" />
              <Skeleton class="h-4 w-32" />
            </div>
          </div>
        </div>
      `
    },
    'image-grid': {
      height: 'min-h-[300px]',
      template: `
        <div class="w-full py-4">
          <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
            <Skeleton v-for="i in 6" :key="i" class="aspect-square rounded-md" />
          </div>
        </div>
      `
    },
    'shadcn-accordion': {
      height: 'min-h-[200px]',
      template: `
        <div class="w-full space-y-2">
          <div v-for="i in 3" :key="i" class="border border-zinc-200 dark:border-zinc-700 rounded-lg p-4">
            <Skeleton class="h-5 w-3/4" />
          </div>
        </div>
      `
    },
    'default': {
      height: 'min-h-[100px]',
      template: `
        <div class="w-full py-4 space-y-4">
          <Skeleton class="h-6 w-3/4" />
          <Skeleton class="h-4 w-full" />
          <Skeleton class="h-4 w-5/6" />
        </div>
      `
    }
  };

  return skeletonConfigs[type] ?? skeletonConfigs['default']!;
}

// Use existing Skeleton component for consistency
import { Skeleton } from '@/Components/ui/skeleton';

// Create skeleton component dynamically based on content type
const createSkeletonComponent = (type: string) => {
  const config = getSkeletonForType(type);
  return {
    components: { Skeleton },
    template: config.template
  };
};

// Cache skeleton components to avoid re-creation
const skeletonCache = new Map<string, ReturnType<typeof createSkeletonComponent>>();
function getSkeletonComponent(type: string) {
  if (!skeletonCache.has(type)) {
    skeletonCache.set(type, createSkeletonComponent(type));
  }
  return skeletonCache.get(type) ?? createSkeletonComponent(type);
}

</script>
