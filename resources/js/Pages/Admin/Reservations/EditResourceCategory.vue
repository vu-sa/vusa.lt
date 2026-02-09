<template>
  <PageContent :title="$tChoice('forms.new_model', 1, {
    model: $tChoice('entities.resource_category.model', 1),
  })
  " :heading-icon="Icons.RESOURCE_CATEGORY">
    <UpsertModelLayout>
      <ResourceCategoryForm :resource-category
        @submit:form="(form) => form.patch(route('resourceCategories.update', resourceCategory.id), { preserveScroll: true })"
        @delete="() => router.delete(route('resourceCategories.destroy', resourceCategory.id))" />
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="tsx">
import Icons from '@/Types/Icons/regular';
import PageContent from '@/Components/Layouts/AdminContentPage.vue';
import ResourceCategoryForm from '@/Components/AdminForms/ResourceCategoryForm.vue';
import UpsertModelLayout from '@/Components/Layouts/FormUpsertLayout.vue';

defineProps<{
  resourceCategory: any;
}>();
</script>
