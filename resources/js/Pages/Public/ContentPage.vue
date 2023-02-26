<template>
  <Head :title="page.title" />

  <FadeTransition appear>
    <section class="max-w-7xl px-8 pt-8 pr-20 last:pb-2 lg:pl-40">
      <header>
        <NBreadcrumb v-if="navigationItemId != null" class="mb-4 flex w-full">
          <NBreadcrumbItem
            v-for="breadcrumb in breadcrumbTree"
            :key="breadcrumb.parent_id"
            :clickable="false"
          >
            {{ breadcrumb.name }}
            <template #separator>
              <NIcon><HatGraduation20Filled /></NIcon>
            </template>
          </NBreadcrumbItem>
        </NBreadcrumb>
      </header>
      <article
        class="grid grid-cols-1 gap-x-12"
        :class="{ 'lg:grid-cols-[1fr_250px]': anchorLinks }"
      >
        <h1 class="col-span-full col-start-1 inline-flex gap-4">
          <span class="text-gray-900 dark:text-white">{{ page.title }}</span>
          <NButton v-if="$page.props.auth?.user" text @click="editPage"
            ><NIcon :size="32" :component="DocumentEdit20Regular"
          /></NButton>
        </h1>
        <!-- eslint-disable-next-line vue/no-v-html -->
        <div class="prose dark:prose-invert" v-html="text" />
        <aside v-if="anchorLinks" class="sticky top-48 hidden h-fit lg:block">
          <NAnchor ignore-gap :bound="120">
            <NAnchorLink
              v-for="link in anchorLinks"
              :key="link.href"
              :title="link.title"
              :href="link.href"
            ></NAnchorLink>
          </NAnchor>
        </aside>
      </article>
    </section>
  </FadeTransition>
</template>

<script setup lang="ts">
import { DocumentEdit20Regular, HatGraduation20Filled } from "@vicons/fluent";
import { Head, router, usePage } from "@inertiajs/vue3";
import {
  NAnchor,
  NAnchorLink,
  NBreadcrumb,
  NBreadcrumbItem,
  NButton,
  NIcon,
} from "naive-ui";

import FadeTransition from "@/Components/Transitions/FadeTransition.vue";

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
      (item) => item.id === navigationItemId
    );
    breadcrumbTree.unshift(navigationItem);
    navigationItemId = navigationItem.parent_id;
  }
  return breadcrumbTree;
};

const breadcrumbTree = getBreadcrumbTree(props.navigationItemId);

const editPage = () => {
  router.visit(route("pages.edit", { id: props.page.id }));
};

// TODO: should be added to backend

const text = props.page.text.replace(/<h[1-6]>(.*?)<\/h[1-6]>/g, (match) => {
  const headingElement = document.createElement("div");
  headingElement.innerHTML = match;
  const headingText = headingElement.textContent;
  const headingId = headingText?.replace(/\s/g, "-").toLowerCase();
  return match.replace(">", ` id="${headingId}">`);
});

const headings = props.page.text.match(/<h[1-6]>(.*?)<\/h[1-6]>/g);

// find headings in page text and create a parsable object for NAnchor

const anchorLinks = headings?.map((heading) => {
  const headingElement = document.createElement("div");
  headingElement.innerHTML = heading;
  const headingText = headingElement.textContent;
  const headingId = headingText?.replace(/\s/g, "-").toLowerCase();
  return {
    title: headingText,
    href: `#${headingId}`,
  };
});

// add ids to text
</script>

<style>
.n-breadcrumb ul {
  display: flex;
  flex-wrap: wrap;
}

* {
  scroll-margin: 100px 0 0 0;
}
</style>
