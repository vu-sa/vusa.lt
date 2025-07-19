<template>
  <Button 
    v-bind="$attrs" 
    variant="ghost" 
    :title="$t('Paieška')" 
    @click="changeShowSearch"
    class="gap-2"
    animation="subtle"
  >
    <IFluentSearch20Filled class="h-4 w-4" />
    <slot />
  </Button>
  <CardModal v-model:show="showSearch" title="Paieška" @close="showSearch = false">
    <NInput :loading="searchInputLoading" round type="text" size="large" :placeholder="$t('Ieškoti...')" class="mb-2"
      @input="handleSearchInput" />
    <Separator v-if="$page.props.search" />
    <div class="flex flex-col gap-4">
      <template v-if="$page.props.search">
        <div v-if="$page.props.search.documents.length !== 0">
          <h3>Dokumentai</h3>

          <div class="col-span-2 mt-8 grid grid-cols-1 gap-6 sm:grid-cols-2">
            <SmartLink v-for="documentItem in $page.props.search.documents" :key="documentItem.id"
              :href="getRoute(documentItem, 'document')" @success="changeShowSearch">
              <DocumentCard :document-item />
            </SmartLink>
          </div>
        </div>
        <div v-if="$page.props.search.pages.length !== 0">
          <h3>Puslapiai</h3>

          <div class="grid content-stretch gap-4 lg:grid-cols-2">
            <Link v-for="page in $page.props.search.pages" :key="page.id" :href="getRoute(page, 'page')"
              @success="changeShowSearch">
            <PageCard :page />
            </Link>
          </div>
        </div>
        <div v-if="$page.props.search?.news.length !== 0">
          <h3 v-if="$page.props.search.news">
            Naujienos
          </h3>

          <div class="grid gap-8 sm:grid-cols-1 md:grid-cols-2">
            <Link v-for="news in $page.props.search.news" :key="news.id" :href="getRoute(news, 'news')">
            <NewsCard :news />
            </Link>
          </div>
        </div>
        <!-- <div v-if="$page.props.search?.calendar.length !== 0">
          <h3 v-if="$page.props.search.calendar">
            Kalendoriaus įrašai
          </h3>
          <Link v-for="calendar in $page.props.search.calendar" :key="calendar.id"
            :href="getRoute(calendar, 'calendar')" @success="changeShowSearch">
          <div class="mb-2 rounded-lg border border-gray-200 bg-white/95 p-4 dark:border-zinc-600 dark:bg-zinc-700/90">
            <p>{{ calendar.title }}</p>
            <p class="text-sm text-gray-500">
              {{ calendar.date }}
            </p>
          </div>
          </Link>
        </div> -->
      </template>
    </div>
  </CardModal>
</template>

<script setup lang="ts">
import { Link, router, usePage } from "@inertiajs/vue3";
import { defineAsyncComponent, ref } from "vue";
import { useDebounceFn } from "@vueuse/core";

import { SEARCH_DEBOUNCE_DELAY } from "@/Constants/navigation";
import CardModal from "@/Components/Modals/CardModal.vue";
import SmartLink from "../SmartLink.vue";
import { Separator } from "@/Components/ui/separator";
import { Button } from "@/Components/ui/button";

const DocumentCard = defineAsyncComponent(() => import("@/Components/Cards/DocumentCard.vue"));
const NewsCard = defineAsyncComponent(() => import("@/Components/Cards/NewsCard.vue"));
const PageCard = defineAsyncComponent(() => import("@/Components/Cards/PageCard.vue"));

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
        only: ["search"],
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
          searchInputLoading.value = false;
        },
      },
    );
  }
}, SEARCH_DEBOUNCE_DELAY);

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
  } else if (type === "document") {
    return model.anonymous_url;
  }
};
</script>
