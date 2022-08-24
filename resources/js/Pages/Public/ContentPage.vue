<template>
  <Head :title="page.title" />

  <FadeTransition appear>
    <article class="grid grid-cols-3 gap-y-4 px-8 pt-8 last:pb-2 lg:px-40">
      <NBreadcrumb
        v-if="navigationItemId != null"
        class="col-span-3 mb-4 flex w-full"
      >
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

      <h1 class="col-span-3 col-start-1 inline-flex gap-4">
        <span>{{ page.title }}</span>
        <NButton v-if="$page.props.user" text @click="editPage"
          ><NIcon :size="32" :component="DocumentEdit20Regular"
        /></NButton>
      </h1>
      <!-- eslint-disable-next-line vue/no-v-html -->
      <div class="prose col-span-3 col-start-1" v-html="page.text"></div>
    </article>
  </FadeTransition>
</template>

<script lang="ts">
import PublicLayout from "@/Components/Public/Layouts/PublicLayout.vue";

export default {
  layout: PublicLayout,
};
</script>

<script setup lang="ts">
import { DocumentEdit20Regular, HatGraduation20Filled } from "@vicons/fluent";
import { Head } from "@inertiajs/inertia-vue3";
import { Inertia } from "@inertiajs/inertia";
import { NBreadcrumb, NBreadcrumbItem, NButton, NIcon } from "naive-ui";
import { usePage } from "@inertiajs/inertia-vue3";
import route from "ziggy-js";

import FadeTransition from "@/Components/Public/Utils/FadeTransition.vue";

const props = defineProps<{
  navigationItemId: number;
  page: Record<string, any>;
}>();

const mainNavigation = usePage().props.value.mainNavigation;

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
  Inertia.visit(route("pages.edit", { id: props.page.id }));
};
</script>

<style>
.n-breadcrumb ul {
  display: flex;
  flex-wrap: wrap;
}
</style>
