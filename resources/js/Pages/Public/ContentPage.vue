<template>
  <section class="pt-8 last:pb-2">
    <article class="grid grid-cols-1 gap-x-12" :class="{ 'lg:grid-cols-[1fr_250px]': anchorLinks }">
      <h1 class="col-span-full col-start-1 inline-flex gap-4">
        <span class="text-gray-900 dark:text-white">{{ page.title }}</span>
      </h1>
      <div class="typography flex max-w-prose flex-col gap-4 py-4 text-base leading-7">
        <RichContentParser :content="page.content?.parts" />
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
// No longer need computed, onMounted, onUnmounted - usePageBreadcrumbs handles lifecycle
import { usePageBreadcrumbs, BreadcrumbHelpers } from '@/Composables/useBreadcrumbsUnified';
import { usePage } from "@inertiajs/vue3";

const props = defineProps<{
  navigationItemId: number;
  page: Record<string, any>;
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

const anchorLinks = props.page.content?.parts?.reduce((acc: any, part: any) => {
  if (part.type === "tiptap") {
    const partHeadings = part.json_content?.content?.filter(
      (node: any) => node.type === "heading",
    );

    // check for h2 and h3 elements, if h3, nest it under h2
    const headings = partHeadings?.reduce((acc: any, node: any) => {
      if (node.attrs.level === 2) {
        acc.push({
          title: node.content[0].text,
          href: `#${node.attrs.id}`,
          children: [],
        });
      } else if (node.attrs.level === 3) {
        // Sometimes the h3 may come before h2, we need to check for that
        if (acc[acc.length - 1]?.children) {
          acc[acc.length - 1]?.children.push({
            title: node.content[0].text,
            href: `#${node.attrs.id}`,
          });
        } else {
          acc.push({
            title: node.content[0].text,
            href: `#${node.attrs.id}`,
            children: [],
          });
        }
      }
      return acc;
    }, []);

    acc.push(...headings);
  }

  return acc;
}, []);
</script>

<style>
.n-breadcrumb ul {
  display: flex;
  flex-wrap: wrap;
}
</style>
