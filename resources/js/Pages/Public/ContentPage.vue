<template>
  <PublicLayout :title="page.title">
    <PageArticle>
      <template #breadcrumb>
        <NBreadcrumb
          v-if="props.navigationItemId != null"
          class="mb-4 h-fit w-fit"
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
      </template>
      <template #title>{{ page.title }} </template>
      <div class="col-span-full mb-4">
        <NButton v-if="$page.props.user" text @click="editPage"
          ><NIcon size="40"
            ><DocumentEdit24Regular></DocumentEdit24Regular></NIcon
        ></NButton>
      </div>
      <!-- eslint-disable-next-line vue/no-v-html -->
      <div class="prose" v-html="page.text"></div>
    </PageArticle>
  </PublicLayout>
</template>

<script setup lang="ts">
import { DocumentEdit24Regular, HatGraduation20Filled } from "@vicons/fluent";
import { Inertia } from "@inertiajs/inertia";
import { NBreadcrumb, NBreadcrumbItem, NButton, NIcon } from "naive-ui";
import { usePage } from "@inertiajs/inertia-vue3";
import route from "ziggy-js";

import PageArticle from "../../components/Public/PageArticle.vue";
import PublicLayout from "../../components/Public/Layouts/PublicLayout.vue";

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
