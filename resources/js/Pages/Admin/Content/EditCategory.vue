<template>
  <PageContent title="Nauja kategorija" :back-url="route('categories.index')">
    <UpsertModelLayout>
      <CategoryForm :category enable-delete
        @submit:form="(form) => form.patch(route('categories.update', category.id), { preserveScroll: true })"
        @delete="() => router.delete(route('categories.destroy', category.id))" />
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="ts">
import { router } from "@inertiajs/vue3";

import CategoryForm from "@/Components/AdminForms/CategoryForm.vue";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";

defineProps<{
  category: App.Entities.Category;
}>();
</script>
