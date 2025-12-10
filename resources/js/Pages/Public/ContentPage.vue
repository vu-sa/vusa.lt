<template>
  <section class="pt-8 last:pb-2">
    <!-- Default layout: content with sidebar for ToC -->
    <article
      v-if="pageLayout === 'default'"
      class="grid grid-cols-1 gap-x-12"
      :class="{ 'lg:grid-cols-[1fr_250px]': anchorLinks && anchorLinks.length > 0 }"
    >
      <h1 class="col-span-full col-start-1 inline-flex gap-4 text-3xl font-bold md:text-4xl">
        <span class="text-gray-900 dark:text-white">{{ page.title }}</span>
      </h1>
      <div class="typography flex max-w-prose flex-col gap-4 py-4 text-base leading-7">
        <RichContentParser :content="(page.content?.parts as unknown as models.ContentPart[]) ?? []" />
      </div>
      <aside v-if="anchorLinks && anchorLinks.length > 0" class="sticky top-48 hidden h-fit lg:block">
        <TableOfContents :links="anchorLinks" :offset="160" />
      </aside>
    </article>
    
    <!-- Wide layout: full width content, great for pages with images/grids -->
    <article v-else-if="pageLayout === 'wide'" class="w-full">
      <h1 class="mb-6 text-3xl font-bold text-gray-900 md:text-4xl lg:text-5xl dark:text-white">{{ page.title }}</h1>
      <div class="typography flex w-full flex-col gap-4 py-4 text-base leading-7">
        <RichContentParser :content="(page.content?.parts as unknown as models.ContentPart[]) ?? []" />
      </div>
    </article>
    
    <!-- Focused layout: centered, narrow reading width for long-form text -->
    <article v-else-if="pageLayout === 'focused'" class="mx-auto max-w-2xl px-4">
      <!-- Optional featured image -->
      <div v-if="page.featured_image" class="mb-8 overflow-hidden rounded-xl">
        <img 
          :src="page.featured_image" 
          :alt="page.title"
          class="h-auto max-h-[400px] w-full object-cover"
        >
      </div>
      <header class="mb-8 text-center">
        <h1 class="text-3xl font-bold text-gray-900 md:text-4xl lg:text-5xl dark:text-white">{{ page.title }}</h1>
        <div v-if="page.meta_description" class="mt-4 text-lg text-muted-foreground">
          {{ page.meta_description }}
        </div>
      </header>
      <div class="typography prose-lg flex flex-col gap-4 py-4 text-lg leading-8">
        <RichContentParser :content="(page.content?.parts as unknown as models.ContentPart[]) ?? []" />
      </div>
    </article>
  </section>
  
  <!-- Mobile ToC (shows for default layout with anchors) -->
  <TableOfContents
    v-if="pageLayout === 'default' && anchorLinks && anchorLinks.length > 0"
    :links="anchorLinks"
    :offset="160"
    mobile-only
    show-mobile-button
  />
  
  <!-- Highlights floating button -->
  <HighlightsFloatingButton :highlights="page.highlights" />
  
  <FeedbackPopover />
</template>

<script setup lang="ts">
import { computed } from "vue";
import { usePage } from "@inertiajs/vue3";

import FeedbackPopover from "@/Components/Public/FeedbackPopover.vue";
import HighlightsFloatingButton from "@/Components/Public/HighlightsFloatingButton.vue";
import RichContentParser from "@/Components/RichContentParser.vue";
import TableOfContents from "@/Components/Public/TableOfContents.vue";
import { usePageBreadcrumbs, BreadcrumbHelpers } from '@/Composables/useBreadcrumbsUnified';

// Type definitions for improved type safety and clarity
interface AnchorLink {
  title: string;
  href: string;
  children: Omit<AnchorLink, 'children'>[];
}

interface HeadingNode {
  type: 'heading';
  attrs: {
    level: 2 | 3;
    id: string;
  };
  content: { text: string }[];
}

interface TipTapNode {
  type: string;
  content?: TipTapNode[];
}

interface PageContentPart {
  id?: number;
  type: 'tiptap' | string;
  json_content?: {
    content?: (TipTapNode | HeadingNode)[];
  };
  [key: string]: any; // Allow other properties
}

interface Page {
  title: string;
  layout?: 'default' | 'wide' | 'focused';
  highlights?: string[] | null;
  meta_description?: string | null;
  featured_image?: string | null;
  content?: {
    parts: PageContentPart[];
  };
}

const props = defineProps<{
  navigationItemId: number;
  page: Page;
}>();

// Compute layout with default fallback
const pageLayout = computed(() => props.page.layout || 'default');

// Set breadcrumbs for content page
usePageBreadcrumbs(() => {
  const page = usePage();
  const mainNavigation = page.props.mainNavigation || [];

  // Build breadcrumb items for the content page
  const navigationPath = BreadcrumbHelpers.buildNavigationPath(props.navigationItemId, mainNavigation);

  // If we have navigation path, use it for breadcrumbs
  if (navigationPath.length > 0) {
    return BreadcrumbHelpers.publicContent(navigationPath);
  }

  // Otherwise just show the current page title
  return BreadcrumbHelpers.publicContent([
    BreadcrumbHelpers.createBreadcrumbItem(props.page.title)
  ]);
});

const anchorLinks = props.page.content?.parts?.reduce((acc: AnchorLink[], part: PageContentPart) => {
  if (part.type === "tiptap" && part.json_content?.content) {
    const partHeadings = part.json_content.content.filter(
      (node): node is HeadingNode => node.type === "heading" && 'attrs' in node && (node.attrs.level === 2 || node.attrs.level === 3)
    );

    // If there are no headings, just return the accumulator
    if (!partHeadings || partHeadings.length === 0) {
      return acc;
    }

    const headings = partHeadings.reduce((headingsAcc: AnchorLink[], node: HeadingNode) => {
      if (node.content && node.content[0] && node.content[0].text) {
        if (node.attrs.level === 2) {
          headingsAcc.push({
            title: node.content[0].text,
            href: `#${node.attrs.id}`,
            children: [],
          });
        } else if (node.attrs.level === 3) {
          const lastHeading = headingsAcc[headingsAcc.length - 1];
          // Ensure that an h2 exists to nest the h3 under
          if (lastHeading?.children) {
            lastHeading.children.push({
              title: node.content[0].text,
              href: `#${node.attrs.id}`,
            });
          } else {
            // If h3 appears without a preceding h2, treat it as a top-level item
            headingsAcc.push({
              title: node.content[0].text,
              href: `#${node.attrs.id}`,
              children: [],
            });
          }
        }
      }
      return headingsAcc;
    }, []);

    // Ensure headings is not undefined before spreading
    if (headings) {
      acc.push(...headings);
    }
  }

  return acc;
}, []) ?? [];
</script>
