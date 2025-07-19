<template>
  <Button 
    v-bind="$attrs" 
    variant="ghost" 
    :title="$t('Paieška')" 
    @click="changeShowSearch"
    class="gap-2"
    animation="subtle"
    :aria-expanded="showSearch"
    aria-haspopup="dialog"
    aria-controls="search-modal"
  >
    <IFluentSearch20Filled class="h-4 w-4" aria-hidden="true" />
    <slot />
    <span v-if="!$slots.default" class="sr-only">{{ $t('Paieška') }}</span>
  </Button>
  <CardModal 
    id="search-modal"
    v-model:show="showSearch" 
    :title="$t('Paieška')" 
    @close="showSearch = false"
    role="dialog"
    aria-labelledby="search-modal-title"
  >
    <h2 id="search-modal-title" class="sr-only">{{ $t('accessibility.site_search') }}</h2>
    <form role="search" @submit.prevent>
      <label for="search-input" class="sr-only">{{ $t('accessibility.enter_search_terms') }}</label>
      <NInput 
        id="search-input"
        :loading="searchInputLoading" 
        round 
        type="search" 
        size="large" 
        :placeholder="$t('Ieškoti...')" 
        class="mb-2"
        @input="handleSearchInput"
        aria-describedby="search-instructions"
      />
      <p id="search-instructions" class="sr-only">{{ $t('accessibility.type_to_search') }}</p>
    </form>
    <Separator v-if="$page.props.search" />
    <div class="flex flex-col gap-4" role="region" aria-live="polite" aria-label="Search results">
      <template v-if="$page.props.search">
        <section v-if="$page.props.search.documents.length !== 0" aria-labelledby="documents-heading">
          <h3 id="documents-heading">{{ $t('accessibility.documents') }}</h3>

          <div class="col-span-2 mt-8 grid grid-cols-1 gap-6 sm:grid-cols-2">
            <SmartLink v-for="documentItem in $page.props.search.documents" :key="documentItem.id"
              :href="getRoute(documentItem, 'document')" @success="changeShowSearch">
              <DocumentCard :document-item />
            </SmartLink>
          </div>
        </section>
        <section v-if="$page.props.search.pages.length !== 0" aria-labelledby="pages-heading">
          <h3 id="pages-heading">{{ $t('accessibility.pages') }}</h3>

          <div class="grid content-stretch gap-4 lg:grid-cols-2">
            <Link v-for="page in $page.props.search.pages" :key="page.id" :href="getRoute(page, 'page')"
              @success="changeShowSearch">
            <PageCard :page />
            </Link>
          </div>
        </section>
        <section v-if="$page.props.search?.news.length !== 0" aria-labelledby="news-heading">
          <h3 id="news-heading" v-if="$page.props.search.news">
            {{ $t('accessibility.news') }}
          </h3>

          <div class="grid gap-8 sm:grid-cols-1 md:grid-cols-2">
            <Link v-for="news in $page.props.search.news" :key="news.id" :href="getRoute(news, 'news')">
            <NewsCard :news />
            </Link>
          </div>
        </section>
        <!-- <section v-if="$page.props.search?.calendar.length !== 0" aria-labelledby="calendar-heading">
          <h3 id="calendar-heading" v-if="$page.props.search.calendar">
            {{ $t('Calendar entries') }}
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
        </section> -->
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
