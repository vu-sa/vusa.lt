<template>
  <PageContent :title="contentType.title" :back-url="route('types.index')">
    <UpsertModelLayout :errors="$page.props.errors" :model="contentType">
      <TypeForm
        :content-types="contentTypes"
        :type="contentType"
        :sharepoint-path="sharepointPath"
        :fileable="{ id: contentType.id, type: 'Type' }"
        model-route="types.update"
      />
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="ts">
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import TypeForm from "@/Components/AdminForms/TypeForm.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";

defineProps<{
  contentType: Record<string, any>;
  contentTypes: Record<string, any>[];
  sharepointPath: string;
}>();
</script>
