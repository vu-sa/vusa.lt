<template>
  <PageContent :title="$tChoice('forms.new_model', 1, {
    model: $tChoice('entities.resource_category.model', 1),
  })
    " :heading-icon="Icons.RESOURCE_CATEGORY">
    <UpsertModelLayout>
      <ResourceCategoryForm :resource-category :categories
        @submit:form="(form) => form.post(route('resourceCategories.store'))" />
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="tsx">
import Icons from "@/Types/Icons/regular";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import ResourceCategoryForm from "@/Components/AdminForms/ResourceCategoryForm.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";

defineProps<{
  categories: any;
}>();

export type ResourceCategoryCreationTemplate = Omit<
  App.Entities.ResourceCategory,
  "created_at" | "updated_at" | "deleted_at" | "id" | "name" | "description"
> & {
  id: undefined;
  name: Record<"lt" | "en", string>;
  description: Record<"lt" | "en", string>;
  // media: models.Media[] | [];
};

const resourceCategory: ResourceCategoryCreationTemplate = {
  id: undefined,
  name: {
    lt: "",
    en: "",
  },
  description: {
    lt: "",
    en: "",
  },
  icon: null,
  resource_category_id: null,
};
</script>
