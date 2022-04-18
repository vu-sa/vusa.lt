<template>
  <PublicLayout :title="page.title">
    <NBreadcrumb
      v-if="props.navigation_item_id != null"
      class="ml-[5vw] pt-4 px-16 lg:px-32"
    >
      <NBreadcrumbItem v-for="breadcrumb in breadcrumbTree" :key="breadcrumb.parent_id">
        <NIcon><HatGraduation20Filled /></NIcon> {{ breadcrumb.name }}
      </NBreadcrumbItem>
    </NBreadcrumb>
    <PageArticle>
      <template #title>{{ page.title }} </template>
      <div class="prose" v-html="page.text"></div>
      <template #randomPages>
        <ul class="prose" v-for="item in random_pages">
          <Link
            :data="{ padalinys: item.alias }"
            :href="route('page', { lang: locale, permalink: item.permalink })"
            preserve-state
            >{{ item.title }}</Link
          >
        </ul>
      </template>
    </PageArticle>
  </PublicLayout>
</template>

<script setup>
import PublicLayout from "../../Layouts/PublicLayout.vue";
import PageArticle from "../../Components/Public/PageArticle.vue";
import { Link, usePage } from "@inertiajs/inertia-vue3";
import { ref } from "vue";
import { NBreadcrumb, NBreadcrumbItem, NIcon } from "naive-ui";
import { HatGraduation20Filled } from "@vicons/fluent";

const props = defineProps({
  navigation_item_id: Number,
  page: Object,
  random_pages: Array,
});

const locale = ref(usePage().props.value.locale);
const mainNavigation = usePage().props.value.mainNavigation;

const getBreadcrumbTree = (navigationItemId) => {
  let breadcrumbTree = [];
  while (navigationItemId) {
    // find array MainNavigation item by navigationItemId and add it to breadcrumbTree
    const navigationItem = mainNavigation.find((item) => item.id === navigationItemId);
    breadcrumbTree.unshift(navigationItem);
    navigationItemId = navigationItem.parent_id;
  }
  return breadcrumbTree;
};

const breadcrumbTree = getBreadcrumbTree(props.navigation_item_id);
</script>
