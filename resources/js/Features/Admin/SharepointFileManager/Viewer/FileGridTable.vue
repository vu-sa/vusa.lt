<template>
  <FadeTransition mode="out-in">
    <TransitionGroup
      v-if="viewMode === 'grid'"
      name="list"
      tag="div"
      class="flex max-w-4xl flex-wrap gap-6"
      @after-enter="afterGroupEnter"
    >
      <div v-for="result in results" :key="result.refIndex">
        <ModelDocumentButton
          :loading="loading"
          :show-thumbnail="showThumbnail"
          :file="result.item"
          @file-button-click="$emit('select:file', result.item)"
        ></ModelDocumentButton>
      </div>
    </TransitionGroup>
    <div v-else-if="viewMode === 'list'" class="rounded-xl">
      <NDataTable
        :bordered="false"
        :data="results"
        :columns="columns"
        :row-key="(row) => row.refIndex"
        :row-props="rowProps"
      ></NDataTable>
    </div>
  </FadeTransition>
</template>

<script setup lang="tsx">
import { FilePdf, FileWord } from "@vicons/fa";
import { NDataTable, NIcon } from "naive-ui";
import { ref } from "vue";

import { fileSize } from "@/Utils/Calc";
import FadeTransition from "@/Components/Transitions/FadeTransition.vue";
import ModelDocumentButton from "./FileButtonSkeletonWrapper.vue";

const emit = defineEmits<{
  (event: "select:file", file: Record<string, any>): void;
}>();

defineProps<{
  results: Array<{ item: MyDriveItem; refIndex: number }>;
  loading: boolean;
  showThumbnail: boolean;
  viewMode: string;
}>();

const absolute = ref(true);

const afterGroupEnter = () => {
  absolute.value = false;
};

const rowProps = (row) => {
  return {
    style: "cursor: pointer; ",
    onClick: () => {
      emit("select:file", row.item);
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
          ) : null}
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
  console.log(mimeType);

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
