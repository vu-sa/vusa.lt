<template>
  <PageForm
    :page
    :available-tags
    :submit-url="route('pages.update', page.id)"
    submit-method="patch"
    enable-delete
    @delete="() => router.delete(route('pages.destroy', page.id))"
  />
</template>

<script setup lang="ts">
import { router } from '@inertiajs/vue3';

import PageForm from '@/Components/AdminForms/PageForm.vue';

defineProps<{
  // `descendant_ids` is server-computed (Page::descendantIds()), not a real relation.
  page: App.Entities.Page & { descendant_ids?: number[] };
  availableTags?: App.Entities.Tag[];
}>();
</script>
