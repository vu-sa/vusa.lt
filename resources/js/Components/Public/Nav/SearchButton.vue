<template>
  <NButton text @click="changeShowSearch">
    <NIcon size="22">
      <Search20Filled />
    </NIcon>
  </NButton>
  <NModal v-model:show="showSearch">
    <div
      class="w-3/4 overflow-auto rounded-md border-2 border-gray-100 bg-white/95 p-4 shadow-lg dark:border-zinc-700 dark:bg-zinc-800/90 md:h-1/2 md:w-1/2"
    >
      <!-- <h3 class="mb-2">Paieška</h3> -->
      <NInput
        :loading="searchInputLoading"
        round
        type="text"
        size="large"
        :placeholder="$t('Ieškoti...')"
        class="mb-4"
        @input="handleSearchInput"
      />
      <div v-if="$page.props.search.pages.length !== 0">
        <h3>Puslapiai</h3>
        <Link
          v-for="page in $page.props.search.pages"
          :href="route('page', { lang: page.lang, permalink: page.permalink })"
        >
          <div
            class="mb-2 rounded-lg border border-gray-200 bg-white/95 py-2 px-4 dark:border-zinc-600 dark:bg-zinc-700/90"
          >
            <p>{{ page.title }}</p>
          </div>
        </Link>
      </div>
      <div v-if="$page.props.search.news.length !== 0">
        <h3 v-if="$page.props.search.news">Naujienos</h3>
        <Link
          v-for="news in $page.props.search.news"
          :href="
            route('news', {
              lang: news.lang,
              newsString: 'naujiena',
              permalink: news.permalink,
            })
          "
        >
          <div
            class="mb-2 rounded-lg border border-gray-200 bg-white/95 p-4 dark:border-zinc-600 dark:bg-zinc-700/90"
          >
            <p>{{ news.title }}</p>
            <p class="text-sm text-gray-500">{{ news.publish_time }}</p>
          </div>
        </Link>
      </div>
      <div v-if="$page.props.search.calendar.length !== 0">
        <h3 v-if="$page.props.search.calendar">Kalendoriaus įrašai</h3>
        <div
          v-for="calendar in $page.props.search.calendar"
          :key="calendar.id"
          class="mb-2 rounded-lg border border-gray-200 bg-white/95 p-4 dark:border-zinc-600 dark:bg-zinc-700/90"
        >
          <p>{{ calendar.title }}</p>
          <p class="text-sm text-gray-500">{{ calendar.date }}</p>
        </div>
      </div>
    </div>
  </NModal>
</template>
<script setup lang="ts">
import { Link, router } from "@inertiajs/vue3";
import { NButton, NIcon, NInput, NModal } from "naive-ui";
import { Search20Filled } from "@vicons/fluent";
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
      route("search"),
      {
        data: { input: input },
      },
      {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
          searchInputLoading.value = false;
        },
      }
    );
  }
}, 500);
</script>
