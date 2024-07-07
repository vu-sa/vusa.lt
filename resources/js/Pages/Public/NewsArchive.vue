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
      subdomain: $page.props.padalinys?.subdomain ?? 'www',
    })
      ">
    <HomeCard :has-mini-content="false" :has-below-card="true">
      <template #mini />
      <template #below-card>
        <!-- <NIcon class="mr-2" size="20"> <CalendarLtr20Regular /> </NIcon>VU SA
                ataskaitinė-rinkiminė konferencija -->
        <IFluentClock24Filled />
        {{ formatStaticTime(new Date(item.publish_time), { year: "numeric", month: "long", day: "numeric" },
          $page.props.app.locale) }}
      </template>
      <template #image>
        <img :src="item.image" class="mb-1 h-40 w-full rounded-sm object-cover shadow-md duration-200 hover:shadow-lg">
      </template>
      {{ item.title }}
    </HomeCard>
    </Link>
  </div>
  <NPagination style="overflow-x: auto" class="mb-2 mt-12" :item-count="props.news.total"
    :page="props.news.current_page" :page-size="15" :show-quick-jumper="true" @update:page="handlePageChange" />
</template>

<script setup lang="ts">
import { Link, router } from "@inertiajs/vue3";

import { formatStaticTime } from "@/Utils/IntlTime";
import HomeCard from "@/Components/Public/HomeCard.vue";

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
