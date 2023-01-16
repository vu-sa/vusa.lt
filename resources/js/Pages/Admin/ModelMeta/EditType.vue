<template>
  <PageContent :title="contentType.title" :back-url="route('types.index')">
    <UpsertModelLayout :errors="$page.props.errors" :model="contentType">
      <TypeForm
        :content-types="contentTypes"
        :type="contentType"
        model-route="types.update"
      />
    </UpsertModelLayout>
    <div class="m-4 flex items-center gap-4">
      <h2 class="mb-0">Dokumentai</h2>
      <NMessageProvider>
        <FileUploaderBasicButton
          @click="showFileUploader = true"
        ></FileUploaderBasicButton>
        <FileUploader
          :show="showFileUploader"
          :sharepoint-file-type-options="sharepointFileTypeOptions"
          :content-model="contentModel"
          @close="showFileUploader = false"
        ></FileUploader>
      </NMessageProvider>
    </div>
    <div class="m-4 flex max-w-4xl flex-wrap gap-6">
      <FileButton
        v-for="document in contentType.sharepointFiles"
        :key="document.id"
        :document="document"
        @click="selectedDocument = document"
      ></FileButton>
    </div>
    <FileSelectDrawer
      :document="selectedDocument"
      @close-drawer="selectedDocument = documentTemplate"
    ></FileSelectDrawer>
  </PageContent>
</template>

<script setup lang="ts">
import { NMessageProvider } from "naive-ui";
import { computed, ref } from "vue";

import { documentTemplate, modelTypes } from "@/Types/formOptions";

import FileButton from "@/Features/Admin/SharepointFileManager/Viewer/FileButton.vue";
import FileSelectDrawer from "@/Features/Admin/SharepointFileManager/Viewer/FileDrawer.vue";
import FileUploader from "@/Features/Admin/SharepointFileManager/FileUploader.vue";
import FileUploaderBasicButton from "@/Features/Admin/SharepointFileManager/FileUploaderBasicButton.vue";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import TypeForm from "@/Components/AdminForms/TypeForm.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";

const props = defineProps<{
  contentType: Record<string, any>;
  contentTypes: Record<string, any>[];
}>();

const selectedDocument = ref(null);
const showFileUploader = ref(false);

const sharepointFileTypeOptions = computed(() => {
  return modelTypes.sharepointFile.map((type) => {
    return {
      label: type,
      value: type,
    };
  });
});

const contentModel = {
  id: props.contentType.id,
  type: "App\\Models\\Type",
  modelTypes: [],
};
</script>
