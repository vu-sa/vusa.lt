<template>
  <div
    class="mt-4 rounded-md border border-zinc-200 p-8 shadow-sm dark:border-zinc-50/10"
  >
    <template v-if="startingPath">
      <div class="flex h-8">
        <div class="flex w-fit gap-2">
          <div class="w-96">
            <NSkeleton v-if="loading" size="medium" round />
            <FuzzySearcher
              v-else
              :data="files"
              @search:results="updateResults"
            />
          </div>
          <NSkeleton v-if="loading" size="medium" circle />
          <NButton v-else circle @click="showFileUploader = true"
            ><template #icon
              ><NIcon :component="DocumentAdd24Regular"></NIcon></template
          ></NButton>
        </div>
        <div class="ml-auto inline-flex items-center gap-4">
          <!-- <NSwitch v-model:value="showThumbnail" :disabled="loading">
            <template #icon><NIcon :component="Image24Regular"></NIcon></template>
          </NSwitch> -->
          <NButton :disabled="loading" circle quaternary @click="refreshFiles"
            ><template #icon
              ><NIcon :component="ArrowClockwise24Filled"></NIcon></template
          ></NButton>
          <NButtonGroup>
            <NButton
              :disabled="loading"
              :type="viewMode === 'grid' ? 'primary' : 'default'"
              @click="viewMode = 'grid'"
              ><template #icon
                ><NIcon :component="Grid20Filled"></NIcon></template
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
      </div>
      <div class="mt-4 flex items-center gap-2">
        <span class="text-xs text-zinc-600 dark:text-zinc-400">Filtrai:</span>
        <FilterPopselect
          :disabled="loading"
          :options="[
            'Visi tipai',
            'Metodinė medžiaga',
            'Protokolai',
            'Veiklą reglamentuojantys dokumentai',
          ]"
          @select:value="contentTypeFilter = $event"
        ></FilterPopselect>
      </div>
      <NDivider />
      <FileViewer
        :results="results"
        :loading="loading"
        :view-mode="viewMode"
        :show-thumbnail="showThumbnail"
        :current-path="path"
        :starting-path="startingPath"
      />
      <FileDrawer
        :file="selectedFile"
        @hide:drawer="selectedFile = null"
      ></FileDrawer>
      <FileUploader
        :show="showFileUploader"
        :fileable="fileable"
        @close="handleFileUploaderClose"
      ></FileUploader>
    </template>
    <p v-else v-once>
      Failų tvarkyklė išjungta, nes institucija nėra priskirta padaliniui.
    </p>
  </div>
</template>

<script setup lang="tsx">
import {
  AppsList20Filled,
  ArrowClockwise24Filled,
  DocumentAdd24Regular,
  Grid20Filled,
} from "@vicons/fluent";
import { NButton, NButtonGroup, NDivider, NIcon, NSkeleton } from "naive-ui";
import { computed, provide, ref, watch } from "vue";
import { useAxios } from "@vueuse/integrations/useAxios";

import { useStorage } from "@vueuse/core";
import FileDrawer from "./FileDrawer.vue";
import FileUploader from "../Uploader/FileUploader.vue";
import FileViewer from "./FileGridTable.vue";
import FilterPopselect from "@/Components/Buttons/FilterPopselect.vue";
import FuzzySearcher from "./FuzzySearcher.vue";

// const emit = defineEmits<{
//   (event: "select:file", file: Record<string, any>): void;
// }>();

const props = defineProps<{
  fileable?: { id: number; type: string };
  startingPath?: string;
}>();

const path = ref(props.startingPath);
const loading = ref(true);
const files = ref<Array<any> | null>(null);
const rawFiles = ref<Array<any> | null>(null);
const showFileUploader = ref(false);
const viewMode = useStorage("fileManager-viewMode", "grid");
const showThumbnail = useStorage("fileManager-showThumbnail", true);
const selectedFile = ref<MyDriveItem | null>(null);
const contentTypeFilter = ref<string | null>(null);

// create 4 mock files for skeleton
const createMockFiles = () => {
  const mockFiles = [];
  for (let i = 0; i < 4; i++) {
    mockFiles.push({
      refIndex: i,
      file: {
        name: "Loading...",
        size: 0,
        lastModifiedDateTime: "Loading...",
        file: {
          mimeType: "Loading...",
        },
      },
    });
  }
  return mockFiles;
};

const mapRawFiles = (files: Array<any>) => {
  return files.map((file, index) => {
    return {
      item: file,
      refIndex: index,
    };
  });
};

const getFiles = async (path: string | null) => {
  if (!path) {
    return;
  }

  const { data, isFinished } = await useAxios(
    route("sharepoint.getDriveItems", { path: path })
  );
  files.value = data.value;
  rawFiles.value = mapRawFiles(data.value);
  loading.value = !isFinished;
  return data;
};

const searchResults = ref<Array<{
  item: MyDriveItem;
  refIndex: number;
}> | null>(null);

const updateResults = (searchItems) => {
  searchResults.value = searchItems;
};

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
    path.value = path.value.split("/").slice(0, -1).join("/");
    return;
  }

  if (file.webUrl === null) {
    return;
  }

  if (file.folder) {
    path.value = path.value + "/" + file.name;
  } else {
    window.open(file.webUrl, "_blank");
  }
};

provide("handleFileSelect", handleFileSelect);
provide("handleFileDblClick", handleFileDblClick);

watch(path, (newPath) => {
  loading.value = true;
  getFiles(newPath);
});

const results = computed(() => {
  let results = searchResults.value ?? rawFiles.value ?? createMockFiles();

  // filter results by content type
  if (contentTypeFilter.value !== "Visi tipai" && contentTypeFilter.value) {
    results = results.filter((result) => {
      return (
        result.item.listItem?.fields?.properties?.Type ===
        contentTypeFilter.value
      );
    });
  }

  return results;
});

getFiles(path.value);

const refreshFiles = () => {
  loading.value = true;
  getFiles(path.value);
};

const handleFileUploaderClose = () => {
  showFileUploader.value = false;
  refreshFiles();
};
</script>
