<template>
  <div>
    <section class="pt-8 last:pb-2">
      <header>
        <NBreadcrumb v-if="navigationItemId != null" class="mb-4 flex w-full">
          <NBreadcrumbItem v-for="breadcrumb in breadcrumbTree" :key="breadcrumb.parent_id" :clickable="false">
            {{ breadcrumb.name }}
            <template #separator>
              <NIcon>
                <HatGraduation20Filled />
              </NIcon>
            </template>
          </NBreadcrumbItem>
        </NBreadcrumb>
      </header>
      <article class="grid grid-cols-1 gap-x-12" :class="{ 'lg:grid-cols-[1fr_250px]': anchorLinks }">
        <h1 class="col-span-full col-start-1 inline-flex gap-4">
          <span class="text-gray-900 dark:text-white">{{ page.title }}</span>
        </h1>
        <!-- eslint-disable-next-line vue/no-v-html -->
        <div class="prose dark:prose-invert">
          <RichContentParser :content="page.contents" />
        </div>
        <!-- <aside v-if="anchorLinks" class="sticky top-48 hidden h-fit lg:block">
          <NAnchor ignore-gap :bound="160">
            <NAnchorLink
              v-for="link in anchorLinks"
              :key="link.href"
              :title="link.title"
              :href="link.href"
            />
          </NAnchor>
        </aside> -->
      </article>
    </section>
  </div>
</template>

<script setup lang="ts">
import { HatGraduation20Filled } from "@vicons/fluent";
import {
  // NAnchor,
  // NAnchorLink,
  NBreadcrumb,
  NBreadcrumbItem,
  NIcon,
} from "naive-ui";
import { usePage } from "@inertiajs/vue3";
import RichContentParser from "@/Components/RichContentParser.vue";

const props = defineProps<{
  navigationItemId: number;
  page: Record<string, any>;
}>();

const mainNavigation = usePage().props.mainNavigation;

const getBreadcrumbTree = (navigationItemId: number) => {
  const breadcrumbTree = [];
  while (navigationItemId) {
    // find array MainNavigation item by navigationItemId and add it to breadcrumbTree
    const navigationItem = mainNavigation.find(
      (item) => item.id === navigationItemId,
    );
    breadcrumbTree.unshift(navigationItem);
    navigationItemId = navigationItem.parent_id;
  }
  return breadcrumbTree;
};

const breadcrumbTree = getBreadcrumbTree(props.navigationItemId);

// TODO: parse headers from content JSON and generate anchor links
</script>

<style>
.n-breadcrumb ul {
  display: flex;
  flex-wrap: wrap;
}

/* offset scroll of content on anchor link click which uses h2 and h3 elements */
*:target {
  scroll-margin-top: 160px;
}
</style>
