<template>
  <PageContent :title="contentType.title.lt" :back-url="route('types.index')">
    <UpsertModelLayout>
      <TypeForm :content-types :all-models-from-model-type :type="contentType" :roles :model-type :sharepoint-path
        enable-delete
        @submit:form="(form) => form.patch(route('types.update', contentType.id), { preserveScroll: true })"
        @delete="() => router.delete(route('types.destroy', contentType.id))" />
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="ts">
import { router } from "@inertiajs/vue3";

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
</script>
