<template>
  <section class="pt-8 last:pb-2">
    <article class="grid grid-cols-1 gap-x-12" :class="{ 'lg:grid-cols-[1fr_250px]': anchorLinks }">
      <h1 class="col-span-full col-start-1 inline-flex gap-4">
        <span class="text-gray-900 dark:text-white">{{ page.title }}</span>
      </h1>
      <div class="typography flex max-w-prose flex-col gap-4 py-4 text-base leading-7">
        <RichContentParser :content="(page.content?.parts as unknown as models.ContentPart[]) ?? []" />
      </div>
      <aside v-if="anchorLinks" class="sticky top-48 hidden h-fit lg:block">
        <NAnchor ignore-gap :bound="160">
          <template v-for="link in anchorLinks" :key="link.href">
            <NAnchorLink :title="link.title" :href="link.href" />
            <template v-for="child in link.children" :key="child.href">
              <NAnchorLink :title="child.title" :href="child.href" :indent="true" />
            </template>
          </template>
        </NAnchor>
      </aside>
    </article>
  </section>
  <FeedbackPopover />
</template>

<script setup lang="ts">
import FeedbackPopover from "@/Components/Public/FeedbackPopover.vue";
import RichContentParser from "@/Components/RichContentParser.vue";
import { usePageBreadcrumbs, BreadcrumbHelpers } from '@/Composables/useBreadcrumbsUnified';
import { usePage } from "@inertiajs/vue3";

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
  content?: {
    parts: PageContentPart[];
  };
}

const props = defineProps<{
  navigationItemId: number;
  page: Page;
}>();

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

<style>
.n-breadcrumb ul {
  display: flex;
  flex-wrap: wrap;
}
</style>
