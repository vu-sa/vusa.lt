<template>
  <PageContent :title="contentType.title" :back-url="route('types.index')">
    <UpsertModelLayout :errors="$attrs.errors" :model="contentType">
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
          :show-object-name="false"
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

<script lang="ts">
import AdminLayout from "@/Components/Admin/Layouts/AdminLayout.vue";

export default {
  layout: AdminLayout,
};
</script>

<script setup lang="ts">
import { NMessageProvider } from "naive-ui";
import { ref } from "vue";
import route from "ziggy-js";

import { contentTypeOptions, documentTemplate } from "@/Composables/someTypes";
import FileButton from "@/Components/Admin/Buttons/FileButton.vue";
import FileSelectDrawer from "@/Components/Admin/Nav/FileSelectDrawer.vue";
import FileUploader from "@/Components/Admin/Buttons/FileUploader.vue";
import FileUploaderBasicButton from "@/Components/Admin/Buttons/FileUploaderBasicButton.vue";
import PageContent from "@/Components/Admin/Layouts/PageContent.vue";
import TypeForm from "@/Components/Admin/Forms/TypeForm.vue";
import UpsertModelLayout from "@/Components/Admin/Layouts/UpsertModelLayout.vue";

const props = defineProps<{
  contentType: Record<string, any>;
  contentTypes: Record<string, any>[];
}>();

const selectedDocument = ref(null);

const contentModel = {
  id: props.contentType.id,
  type: "App\\Models\\Type",
};
</script>
