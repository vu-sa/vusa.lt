<template>
  <h2 class="pt-8 text-gray-900 dark:text-zinc-50">
    Naujienų archyvas
  </h2>
  <NPagination style="overflow-x: auto" class="mb-2 mt-8" :item-count="props.news.total" :page="props.news.current_page"
    :page-size="15" :show-quick-jumper="true" @update:page="handlePageChange" />
  <div class="grid gap-8 pt-8 md:grid-cols-2 lg:grid-cols-4">
    <Link v-for="item in props.news.data" :key="item.id" :href="route('news', {
      lang: item.lang,
      news: item.permalink ?? '',
      newsString: 'naujiena',
      subdomain: $page.props.tenant?.subdomain ?? 'www',
    })
      ">
    <NewsCard :news="item" />
    </Link>
  </div>
  <NPagination style="overflow-x: auto" class="mb-2 mt-12" :item-count="props.news.total"
    :page="props.news.current_page" :page-size="15" :show-quick-jumper="true" @update:page="handlePageChange" />
</template>

<script setup lang="ts">
import { Link, router } from "@inertiajs/vue3";

import NewsCard from "../../Components/Cards/NewsCard.vue";

const props = defineProps<{
  news: PaginatedModels<App.Entities.News>;
}>();

const handlePageChange = (page) => {
  router.reload({
    data: {
      page: page,
    },
    only: ["news"],
    preserveScroll: true,
    onSuccess: () => {
      //   loading.value = false;
    },
  });
};
</script>
