<template>
  <AdminContentPage title="Sharepoint failai">
    <SharepointFileManager
      :files="sharepointDriveItems"
      @select:file="handleClick"
    ></SharepointFileManager>
  </AdminContentPage>
</template>

<script setup lang="tsx">
import { provide } from "vue";

import AdminContentPage from "@/Components/Layouts/AdminContentPage.vue";
import SharepointFileManager from "@/Features/Admin/SharepointFileManager/Viewer/FileManager.vue";

const props = defineProps<{
  sharepointDriveItems: MyDriveItem[];
  path: string;
}>();

provide("sharepointPath", props.path);

const handleClick = (file: Record<string, any>) => {
  console.log(file);
};
</script>
