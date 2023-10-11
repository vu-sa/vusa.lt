<template>
  <FadeTransition mode="out-in">
    <div
      v-if="results.length === 0"
      class="flex flex-col items-center justify-center gap-2 text-zinc-400"
    >
      <NIcon :size="24" :component="Icons.SHAREPOINT_FILE"></NIcon>
      <span>{{ $t("Failų nėra") }}.</span>
    </div>
    <TransitionGroup
      v-else-if="viewMode === 'grid'"
      name="list"
      tag="div"
      class="flex flex-wrap gap-6"
      @after-enter="afterGroupEnter"
    >
      <div v-if="currentPath !== startingPath">
        <ModelDocumentButton
          :loading="loading"
          :show-thumbnail="showThumbnail"
          :file="backButton.item"
        ></ModelDocumentButton>
      </div>
      <div v-for="result in results" :key="result.refIndex">
        <ModelDocumentButton
          :loading="loading"
          :show-thumbnail="showThumbnail"
          :file="result.item"
        ></ModelDocumentButton>
      </div>
    </TransitionGroup>
    <div v-else-if="viewMode === 'list'" class="rounded-xl">
      <FadeTransition mode="out-in">
        <div v-if="loading">
          <NDataTable :loading="loading"></NDataTable>
        </div>
        <div v-else>
          <NDataTable
            :bordered="false"
            :loading="loading"
            :data="resultsWithBackButton"
            :columns="columns"
            :row-key="(row) => row.refIndex"
            :row-props="rowProps"
          ></NDataTable>
        </div>
      </FadeTransition>
    </div>
  </FadeTransition>
</template>

<script setup lang="tsx">
import { FilePdf, FileWord, Folder } from "@vicons/fa";
import { NDataTable, NIcon, NSkeleton } from "naive-ui";
import { computed, inject, ref } from "vue";

import { fileSize } from "@/Utils/Calc";
import FadeTransition from "@/Components/Transitions/FadeTransition.vue";
import Icons from "@/Types/Icons/filled";
import ModelDocumentButton from "./FileButtonSkeletonWrapper.vue";
import type { DriveItem } from "@microsoft/microsoft-graph-types";

const props = defineProps<{
  loading: boolean;
  startingPath: string;
  currentPath: string;
  results: Array<{ item: MyDriveItem; refIndex: number }>;
  showThumbnail: boolean;
  viewMode: string;
}>();

const absolute = ref(true);

const afterGroupEnter = () => {
  absolute.value = false;
};

const handleFileSelect = inject<(file: DriveItem) => void>(
  "handleFileSelect",
  () => {},
);

const handleFileDblClick = inject<(file: DriveItem) => void>(
  "handleFileDblClick",
  () => {},
);

const backButton = {
  item: {
    name: "...",
  },
  refIndex: -1,
};

// compute results where the first item is the back button
const resultsWithBackButton = computed(() => {
  if (props.results.length === 0) {
    return [];
  }

  if (props.currentPath === props.startingPath) {
    return props.results;
  }

  return [backButton, ...props.results];
});

const rowProps = (row) => {
  return {
    style: "cursor: pointer; ",
    onClick: () => {
      handleFileSelect(row.item);
    },
    ondblclick: () => {
      handleFileDblClick(row.item);
    },
  };
};

const columns = [
  {
    width: 30,
    key: "icon",
    render(row) {
      return (
        <div class="flex">
          {row.item.file ? (
            <NIcon component={fileIcon(row.item.file.mimeType)}></NIcon>
          ) : (
            <NIcon component={row.item.folder ? Folder : undefined}></NIcon>
          )}
        </div>
      );
    },
  },
  {
    title: "Pavadinimas",
    key: "item.name",
  },
  {
    title: "Dydis",
    key: "item.size",
    render(row) {
      return fileSize(row.item.size);
    },
  },
  {
    title: "Tipas",
    key: "item.listItem.fields.additionalData.Type",
    sorter: "default",
  },
];

const fileIcon = (mimeType: string | null) => {
  if (mimeType === null) {
    return File;
  }

  if (
    mimeType ===
    "application/vnd.openxmlformats-officedocument.wordprocessingml.document"
  ) {
    return FileWord;
  }

  if (mimeType === "application/pdf") {
    return FilePdf;
  } else {
    return File;
  }
};
</script>

<style scoped>
.list-move,
.list-enter-active,
.list-leave-active {
  transition: opacity 0.5s ease-out;
}

.list-enter-from,
.list-leave-to {
  opacity: 0;
  /* transform: translateX(30px); */
}
.list-leave-active {
  position: v-bind('absolute ? "absolute" : "initial"');
}

div.n-data-table {
  --n-merged-th-color: transparent;
  --n-merged-td-color: transparent;
  --n-merged-border-color: transparent;
}
</style>
