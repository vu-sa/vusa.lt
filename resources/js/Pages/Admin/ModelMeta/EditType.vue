<template>
  <PageContent :title="contentType.title" :back-url="route('types.index')">
    <UpsertModelLayout :errors="$page.props.errors" :model="contentType">
      <TypeForm
        :content-types="contentTypes"
        :type="contentType"
        model-route="types.update"
      />
    </UpsertModelLayout>
    <FileManager
      :starting-path="sharepointPath"
      :fileable="{ id: contentType.id, type: 'Type' }"
    ></FileManager>
  </PageContent>
</template>

<script setup lang="ts">
import FileManager from "@/Features/Admin/SharepointFileManager/Viewer/FileManager.vue";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import TypeForm from "@/Components/AdminForms/TypeForm.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";

defineProps<{
  contentType: Record<string, any>;
  contentTypes: Record<string, any>[];
  sharepointPath: string;
}>();
</script>
