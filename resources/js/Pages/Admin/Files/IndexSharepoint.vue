<template>
  <AdminContentPage title="Sharepoint failai">
    <SharepointFileManager :starting-path="path" />
  </AdminContentPage>
</template>

<script setup lang="tsx">
import AdminContentPage from "@/Components/Layouts/AdminContentPage.vue";
import SharepointFileManager from "@/Features/Admin/SharepointFileManager/Viewer/FileManager.vue";

defineProps<{
  // sharepointDriveItems: MyDriveItem[];
  path: string;
}>();
</script>
