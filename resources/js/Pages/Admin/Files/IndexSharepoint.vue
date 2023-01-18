<template>
  <AdminContentPage title="Sharepoint failai">
    <ModelsDocumentViewer
      :files="sharepointDriveItems"
      @select:file="handleClick"
    ></ModelsDocumentViewer>
  </AdminContentPage>
</template>

<script setup lang="tsx">
import AdminContentPage from "@/Components/Layouts/AdminContentPage.vue";
import ModelsDocumentViewer from "@/Features/Admin/SharepointFileManager/Viewer/FileManager.vue";

defineProps<{
  sharepointDriveItems: MyDriveItem[];
}>();

const handleClick = (file: Record<string, any>) => {
  console.log(file);
};
</script>
