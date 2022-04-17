<template>
  <PublicLayout :title="page.title">
    <PageArticle>
      <template #title>{{ page.title }} </template>
      <div class="prose" v-html="page.text"></div>
      <template #randomPages>
        <ul class="prose" v-for="item in random_pages">
          <Link
            :data="{ padalinys: item.alias }"
            :href="route('page', { lang: locale, permalink: item.permalink })"
            preserve-state
            >{{ item.title }}</Link
          >
        </ul>
      </template>
    </PageArticle>
  </PublicLayout>
</template>

<script setup>
import PublicLayout from "../../Layouts/PublicLayout.vue";
import PageArticle from "../../Components/Public/PageArticle.vue";
import { Link, usePage } from "@inertiajs/inertia-vue3";
import { ref } from "vue";

const locale = ref(usePage().props.value.locale);

const props = defineProps({
  page: Object,
  random_pages: Array,
});
</script>
