<template>
  <NewsForm
    :news
    :available-tags
    :submit-url="route('news.update', news.id)"
    submit-method="patch"
    enable-delete
    @delete="() => router.delete(route('news.destroy', news.id))"
  />
</template>

<script setup lang="ts">
import { router } from '@inertiajs/vue3';

import NewsForm from '@/Components/AdminForms/NewsForm.vue';

defineProps<{
  news: App.Entities.News;
  availableTags?: App.Entities.Tag[];
}>();
</script>
