<template>
  <PublicLayout :title="page.title">
    <NBreadcrumb
      v-if="props.navigation_item_id != null"
      class="ml-[5vw] pt-4 px-8 lg:px-32"
    >
      <NBreadcrumbItem
        v-for="breadcrumb in breadcrumbTree"
        :key="breadcrumb.parent_id"
      >
        <NIcon><HatGraduation20Filled /></NIcon> {{ breadcrumb.name }}
      </NBreadcrumbItem>
    </NBreadcrumb>

    <PageArticle>
      <template #title>{{ page.title }} </template>
      <div class="col-span-full mb-4">
        <NButton v-if="$page.props.user" text @click="editPage"
          ><NIcon size="40"
            ><DocumentEdit24Regular></DocumentEdit24Regular></NIcon
        ></NButton>
      </div>
      <div class="prose" v-html="page.text"></div>
      <!-- <template #randomPages>
        <ul class="prose" v-for="item in random_pages">
          <Link
            :data="{ padalinys: item.alias }"
            :href="route('page', { lang: locale, permalink: item.permalink })"
            preserve-state
            >{{ item.title }}</Link
          >
        </ul>
      </template> -->
    </PageArticle>
  </PublicLayout>
</template>

<script setup lang="ts">
import { DocumentEdit24Regular, HatGraduation20Filled } from "@vicons/fluent";
import { Inertia } from "@inertiajs/inertia";
import { Link, usePage } from "@inertiajs/inertia-vue3";
import { NBreadcrumb, NBreadcrumbItem, NButton, NIcon } from "naive-ui";
import { ref } from "vue";
import PageArticle from "../../Components/Public/PageArticle.vue";
import PublicLayout from "../../Layouts/PublicLayout.vue";

const props = defineProps({
  navigation_item_id: Number,
  page: Object,
  // random_pages: Array,
});

const locale = ref(usePage().props.value.locale);
const mainNavigation = usePage().props.value.mainNavigation;

const getBreadcrumbTree = (navigationItemId) => {
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

const breadcrumbTree = getBreadcrumbTree(props.navigation_item_id);

const editPage = () => {
  Inertia.visit(route("pages.edit", { id: props.page.id }));
};
</script>
