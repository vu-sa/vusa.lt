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
        <FileUploader
          :button="FileUploaderBasicButton"
          :content-type-options="contentTypeOptions"
          :content-model="contentModel"
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
import { ref } from "vue";

import { contentTypeOptions, documentTemplate } from "@/Composables/someTypes";

import FileButton from "@/Components/SharepointFileManager/FileButton.vue";
import FileSelectDrawer from "@/Components/SharepointFileManager/FileDrawer.vue";
import FileUploader from "@/Components/SharepointFileManager/FileUploader.vue";
import FileUploaderBasicButton from "@/Components/SharepointFileManager/FileUploaderBasicButton.vue";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import TypeForm from "@/Components/AdminForms/TypeForm.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";

const props = defineProps<{
  contentType: Record<string, any>;
  contentTypes: Record<string, any>[];
}>();

const selectedDocument = ref(null);

const contentModel = {
  id: props.contentType.id,
  type: "App\\Models\\Type",
  modelTypes: [],
};
</script>
