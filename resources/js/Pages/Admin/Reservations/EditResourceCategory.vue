<template>
  <PageContent :title="editEntityTitle('resourceCategory')" :heading-icon="CategoryIcon">
    <UpsertModelLayout>
      <ResourceCategoryForm :resource-category
        @submit:form="(form) => form.patch(route('resourceCategories.update', resourceCategory.id), { preserveScroll: true })"
        @delete="() => router.delete(route('resourceCategories.destroy', resourceCategory.id))" />
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="tsx">
import { editEntityTitle } from '@/Utils/EntityMessages';
import PageContent from '@/Components/Layouts/AdminContentPage.vue';
import ResourceCategoryForm from '@/Components/AdminForms/ResourceCategoryForm.vue';
import UpsertModelLayout from '@/Components/Layouts/FormUpsertLayout.vue';
import { CategoryIcon } from '@/Components/icons';

defineProps<{
  resourceCategory: any;
}>();
</script>
