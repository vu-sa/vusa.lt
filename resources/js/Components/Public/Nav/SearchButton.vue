<template>
  <NButton :title="$t('Paieška')" text @click="changeShowSearch">
    <template #icon>
      <IFluentSearch20Filled />
    </template>
  </NButton>
  <NModal v-model:show="showSearch">
    <div
      class="w-3/4 overflow-auto rounded-md border-2 border-gray-100 bg-white/95 p-4 shadow-lg dark:border-zinc-700 dark:bg-zinc-800/90 md:size-1/2">
      <!-- <h3 class="mb-2">Paieška</h3> -->
      <NInput :loading="searchInputLoading" round type="text" size="large" :placeholder="$t('Ieškoti...')" class="mb-4"
        @input="handleSearchInput" />
      <div v-if="$page.props.search.pages.length !== 0">
        <h3>Puslapiai</h3>
        <Link v-for="page in $page.props.search.pages" :key="page.id" :href="getRoute(page, 'page')"
          @success="changeShowSearch">
        <div
          class="mb-2 rounded-lg border border-gray-200 bg-white/95 px-4 py-2 dark:border-zinc-600 dark:bg-zinc-700/90">
          <p>{{ page.title }}</p>
        </div>
        </Link>
      </div>
      <div v-if="$page.props.search.news.length !== 0">
        <h3 v-if="$page.props.search.news">
          Naujienos
        </h3>
        <Link v-for="news in $page.props.search.news" :key="news.id" :href="getRoute(news, 'news')"
          @success="changeShowSearch">
        <div class="mb-2 rounded-lg border border-gray-200 bg-white/95 p-4 dark:border-zinc-600 dark:bg-zinc-700/90">
          <p>{{ news.title }}</p>
          <p class="text-sm text-gray-500">
            {{ news.publish_time }}
          </p>
        </div>
        </Link>
      </div>
      <div v-if="$page.props.search.calendar.length !== 0">
        <h3 v-if="$page.props.search.calendar">
          Kalendoriaus įrašai
        </h3>
        <Link v-for="calendar in $page.props.search.calendar" :key="calendar.id" :href="getRoute(calendar, 'calendar')"
          @success="changeShowSearch">
        <div class="mb-2 rounded-lg border border-gray-200 bg-white/95 p-4 dark:border-zinc-600 dark:bg-zinc-700/90">
          <p>{{ calendar.title }}</p>
          <p class="text-sm text-gray-500">
            {{ calendar.date }}
          </p>
        </div>
        </Link>
      </div>
    </div>
  </NModal>
</template>
<script setup lang="ts">
import { Link, router, usePage } from "@inertiajs/vue3";
import { ref } from "vue";
import { useDebounceFn } from "@vueuse/core";

const showSearch = ref(false);
const searchInputLoading = ref(false);

const changeShowSearch = () => {
  showSearch.value = !showSearch.value;
};

const handleSearchInput = useDebounceFn((input) => {
  if (input.length > 2) {
    searchInputLoading.value = true;
    router.post(
      route("search", { lang: usePage().props.app.locale }),
      {
        data: { input: input },
      },
      {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
          searchInputLoading.value = false;
        },
      },
    );
  }
}, 500);

const getRoute = (model: Record<string, any>, type: string) => {
  if (type === "page") {
    return route("page", {
      lang: model.lang,
      subdomain: usePage().props.tenant?.subdomain ?? "www",
      permalink: model?.permalink,
    });
  } else if (type === "news") {
    return route("news", {
      lang: model?.lang,
      news: model?.permalink,
      newsString: "naujiena",
      subdomain: usePage().props.tenant?.subdomain ?? "www",
    });
  } else if (type === "calendar") {
    return route("calendar.event", {
      calendar: model?.id,
      lang: model?.lang,
    });
  }
};
</script>
