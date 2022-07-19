<template>
  <PublicLayout>
    <h2 class="pt-8 px-8 lg:px-32">Naujienų archyvas</h2>
    <NPagination
      style="overflow-x: auto"
      class="pt-8 px-8 lg:px-32"
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
    <div class="grid md:grid-cols-2 lg:grid-cols-4 pt-8 px-8 lg:px-32 gap-8">
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
              class="rounded-sm shadow-md hover:shadow-lg duration-200 h-40 w-full mb-1 object-cover"
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
  </PublicLayout>
</template>

<script setup lang="ts">
import { Clock20Regular } from "@vicons/fluent";
import { Inertia } from "@inertiajs/inertia";
import { Link, usePage } from "@inertiajs/inertia-vue3";
import { NIcon, NPagination } from "naive-ui";
import { reactive, ref } from "vue";
import ContactWithPhoto from "@/Components/Public/ContactWithPhoto.vue";
import HomeCard from "@/Components/Public/HomeCard.vue";
import PublicLayout from "@/Layouts/PublicLayout.vue";

const props = defineProps({
  news: Object,
});

const locale = ref(usePage().props.value.locale);

const handlePageChange = (page) => {
  console.log(page);
  Inertia.reload({
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
