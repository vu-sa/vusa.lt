<template>
  <PublicLayout>
    <h2 class="pt-8 px-8 lg:px-32">Naujienų archyvas</h2>
    <NPagination
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
    <div class="grid grid-cols-3 pt-8 px-8 lg:px-32 gap-8">
      <Link
        class="col-span-2"
        :href="
          route('main.news', {
            permalink: news.permalink,
            lang: news.lang,
            newsString: 'naujiena',
          })
        "
        v-for="news in props.news.data"
        :key="news.id"
      >
        <ContactWithPhoto>
          <template #image>
            <img
              :src="news.image"
              class="rounded-sm shadow-md hover:shadow-lg duration-200 h-auto max-h-48 w-full mb-1 object-cover col-span-1 col-start-1"
            />
          </template>
          <template #name>{{ news.title }}</template>
          <template #description
            ><p class="font-normal line-clamp-3" v-html="news.short"
          /></template>
          <template #email>{{ news.publish_time }}</template>
        </ContactWithPhoto>
      </Link>
    </div>
  </PublicLayout>
</template>

<script setup>
import PublicLayout from "@/Layouts/PublicLayout.vue";
import { NPagination } from "naive-ui";
import { reactive, ref } from "vue";
import { Inertia } from "@inertiajs/inertia";
import { usePage, Link } from "@inertiajs/inertia-vue3";
import ContactWithPhoto from "@/Components/Public/ContactWithPhoto.vue";

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
