<template>
  <PageContent :title="contentType.title.lt" :back-url="route('types.index')">
    <UpsertModelLayout>
      <TypeForm
        :content-types="contentTypes"
        :all-models-from-model-type="allModelsFromModelType"
        :type="contentType"
        :roles="roles"
        :model-type="modelType"
        :sharepoint-path="sharepointPath"
        @submit:form="handleSubmit"
      />
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="ts">
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import TypeForm from "@/Components/AdminForms/TypeForm.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";

const props = defineProps<{
  contentType: App.Entities.Type;
  contentTypes: App.Entities.Type[];
  sharepointPath: string;
  allModelsFromModelType?: Record<string, any>[];
  modelType?: string;
  roles?: App.Entities.Role[];
}>();

const handleSubmit = (form: any) => {
  form.patch(route("types.update", props.contentType.id), {
    preserveScroll: true,
  });
};
</script>
