<template>
  <div>
    <div class="flex flex-wrap items-center gap-4">
      <FileButtonSkeletonWrapper
        v-for="file in data"
        :key="file.id"
        :file="file"
        :small="true"
        :show-thumbnail="true"
      ></FileButtonSkeletonWrapper>
    </div>
    <FileDrawer
      :file="selectedFile"
      @hide:drawer="selectedFile = null"
    ></FileDrawer>
  </div>
</template>

<script setup lang="tsx">
import { provide, ref } from "vue";
import { useAxios } from "@vueuse/integrations/useAxios";
import FileButtonSkeletonWrapper from "./FileButtonSkeletonWrapper.vue";
import FileDrawer from "./FileDrawer.vue";

const props = defineProps<{
  fileable: {
    id: number;
    type: string;
  };
}>();

const selectedFile = ref(null);

const { data } = await useAxios(
  route("sharepoint.getTypesDriveItems", {
    type: props.fileable.type,
    id: props.fileable.id,
  })
);

const handleFileSelect = (file: MyDriveItem) => {
  if (!file.file) {
    selectedFile.value = null;
    return;
  }

  selectedFile.value = file;
};

// const handleMaskClick = () => {
//   selectedFile.value = null;
// };

const handleFileDblClick = (file: MyDriveItem) => {
  if (file.name === "...") {
    // remove last folder from path
    return;
  }

  if (file.webUrl === null) {
    return;
  }

  if (file.folder) {
    return;
  } else {
    window.open(file.webUrl, "_blank");
  }
};

provide("handleFileSelect", handleFileSelect);
provide("handleFileDblClick", handleFileDblClick);
</script>
