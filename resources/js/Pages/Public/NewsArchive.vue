<template>
  <FadeTransition appear>
    <div>
      <h2 class="px-8 pt-8 text-gray-900 dark:text-zinc-50 lg:px-32">
        Naujienų archyvas
      </h2>
      <NPagination
        style="overflow-x: auto"
        class="px-8 pt-8 lg:px-32"
        :item-count="props.news.total"
        :page="props.news.current_page"
        :page-size="15"
        :show-quick-jumper="true"
        @update:page="handlePageChange"
      ></NPagination>
      <!-- <NSelect
        v-model:value="value"
        filterable
        placeholder="Search Songs"
        :options="options"
        :loading="loading"
        clearable
        remote
        @search="handleSearch"
      /> -->
      <div class="grid gap-8 px-8 pt-8 md:grid-cols-2 lg:grid-cols-4 lg:px-32">
        <HomeCard
          v-for="item in props.news.data"
          :key="item.id"
          :has-mini-content="false"
          :has-below-card="true"
        >
          <template #mini> </template>
          <template #below-card>
            <!-- <NIcon class="mr-2" size="20"> <CalendarLtr20Regular /> </NIcon>VU SA
              ataskaitinė-rinkiminė konferencija -->
            <NIcon class="mr-2" size="20"> <Clock20Regular /> </NIcon
            >{{ item.publish_time }}
          </template>
          <template #image>
            <Link
              :href="
                route('news', {
                  lang: item.lang,
                  newsString: 'naujiena',
                  padalinys: item.alias,
                  permalink: item.permalink,
                })
              "
              ><img
                :src="item.image"
                class="mb-1 h-40 w-full rounded-sm object-cover shadow-md duration-200 hover:shadow-lg"
            /></Link>
          </template>
          <Link
            :href="
              route('news', {
                lang: item.lang,
                newsString: 'naujiena',
                padalinys: item.alias,
                permalink: item.permalink,
              })
            "
            >{{ item.title }}</Link
          >
        </HomeCard>
      </div>
    </div>
  </FadeTransition>
</template>

<script setup lang="ts">
import { Clock20Regular } from "@vicons/fluent";
import { Head, Link, router, usePage } from "@inertiajs/vue3";
import { NIcon, NPagination } from "naive-ui";
import { ref } from "vue";

import FadeTransition from "@/Components/Transitions/FadeTransition.vue";
import HomeCard from "@/Components/Public/HomeCard.vue";

const props = defineProps<{
  news: PaginatedModels<App.Entities.News>;
}>();

const locale = ref(usePage().props.locale);

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
