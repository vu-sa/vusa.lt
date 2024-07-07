<template>
  <PageContent :title="$tChoice('forms.new_model', 1, {
    model: $tChoice('entities.resource_category.model', 1),
  })
    " :heading-icon="Icons.RESOURCE_CATEGORY">
    <UpsertModelLayout :errors="$page.props.errors" :model="resourceCategory">
      <ResourceCategoryForm :resource-category="resourceCategory" model-route="resourceCategories.update"
        delete-model-route="resourceCategories.delete" />
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="tsx">
import Icons from "@/Types/Icons/regular";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import ResourceCategoryForm from "@/Components/AdminForms/ResourceCategoryForm.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";

defineProps<{
  resourceCategory: any;
}>();
</script>
