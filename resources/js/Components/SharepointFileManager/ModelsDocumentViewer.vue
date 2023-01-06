<template>
  <div
    class="mt-4 rounded-md border border-zinc-200 p-8 shadow-sm dark:border-zinc-50/10"
  >
    <!-- <h3 class="mb-4">Dokumentai</h3> -->
    <div class="flex h-8">
      <div class="w-fit">
        <FadeTransition mode="out-in">
          <FuzzySearcher
            v-if="!loading"
            key="search"
            :data="loadedDocuments"
            :disabled="loadedDocuments.length === 0"
            @update-results="updateResults"
          />
          <!-- <NSkeleton
            v-else
            key="skeleton"
            height="36px"
            width="384px"
            round
            class="w-96"
          /> -->
        </FadeTransition>
      </div>
      <NButtonGroup class="ml-auto">
        <NButton
          :disabled="loading"
          :type="viewMode === 'grid' ? 'primary' : 'default'"
          @click="viewMode = 'grid'"
          ><template #icon><NIcon :component="Grid20Filled"></NIcon></template
        ></NButton>
        <NButton
          :disabled="loading"
          :type="viewMode === 'list' ? 'primary' : 'default'"
          @click="viewMode = 'list'"
          ><template #icon
            ><NIcon :component="AppsList20Filled"></NIcon></template
        ></NButton>
      </NButtonGroup>
    </div>
    <NDivider />
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
            :document="result.item"
            @file-button-click="$emit('fileButtonClick', result.item)"
          ></ModelDocumentButton>
        </div>
      </TransitionGroup>
      <div v-else-if="viewMode === 'list'" class="rounded-xl">
        <NDataTable
          :bordered="false"
          :data="results"
          :columns="columns"
          :row-props="rowProps"
        ></NDataTable>
      </div>
    </FadeTransition>
  </div>
</template>

<script setup lang="tsx">
import { AppsList20Filled, Grid20Filled } from "@vicons/fluent";
import { File, FilePdf, FileWord } from "@vicons/fa";
import { NButton, NButtonGroup, NDataTable, NDivider, NIcon } from "naive-ui";
import { onMounted, ref } from "vue";
import axios from "axios";

import FadeTransition from "@/Components/Transitions/FadeTransition.vue";
import FuzzySearcher from "@/Components/SharepointFileManager/FuzzySearcher.vue";
import ModelDocumentButton from "@/Components/SharepointFileManager/ModelDocumentButton.vue";

const emit = defineEmits<{
  (event: "fileButtonClick", document: Record<string, any>): void;
}>();

const props = defineProps<{
  modelCollectionWithDocuments: Array<Record<string, any>>;
}>();

const loading = ref(true);
const absolute = ref(true);
const viewMode = ref("grid");

const loadedDocuments = ref([]);

const updateResults = (newResults) => {
  results.value = newResults;
};

const mapDocuments = () => {
  return props.modelCollectionWithDocuments
    .map((model) => {
      return model.documents;
    })
    .flat();
};

const results = ref(mapDocuments());

const getFiles = async () => {
  let documents = mapDocuments();

  let documentIDArray = documents.map((document) => {
    return document.id;
  });

  axios
    .post(route("sharepoint.getFiles"), { documentIds: documentIDArray })
    .then((response) => {
      loadedDocuments.value = response.data;
      results.value = response.data.map((document, index) => {
        return {
          item: document,
          refIndex: index,
        };
      });
      loading.value = false;
    });
};

const rowProps = (row) => {
  return {
    style: "cursor: pointer; ",
    onClick: () => {
      emit("fileButtonClick", row.item);
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
          <NIcon component={fileIcon(row.item.file.mimeType)}></NIcon>
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
    key: "item.type",
  },
];

const fileSize = (size) => {
  const bytes = size;
  const sizes = ["Bytes", "KB", "MB", "GB", "TB"];
  if (bytes === 0) return "0 Byte";
  const i = parseInt(Math.floor(Math.log(bytes) / Math.log(1024)).toString());
  return Math.round(bytes / Math.pow(1024, i), 2) + " " + sizes[i];
};

const fileIcon = (mimeType) => {
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

const afterGroupEnter = () => {
  absolute.value = false;
  console.log(absolute);
};

onMounted(() => {
  getFiles();
});
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
