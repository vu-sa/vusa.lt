<template>
  <div class="mt-4 rounded-md border border-zinc-200 p-8 shadow-xs dark:border-zinc-50/10">
    <template v-if="startingPath">
      <div class="flex flex-wrap gap-4">
        <div class="flex w-fit flex-wrap items-center gap-2">
          <div class="w-96">
            <Skeleton v-if="loading" class="h-10 w-full rounded-full" />
            <FuzzySearcher v-else :data="files" @search:results="updateResults" />
          </div>
          <Skeleton v-if="loading" class="h-10 w-10 rounded-full" />
          <Button v-else class="rounded-full" @click="showFileUploader = true">
            <IFluentDocumentAdd24Regular />
            {{ $t('forms.add') }}
          </Button>
        </div>
        <div class="ml-auto inline-flex items-center gap-4">
          <!-- <NSwitch v-model:value="showThumbnail" :disabled="loading">
            <template #icon><NIcon :component="Image24Regular"></NIcon></template>
          </NSwitch> -->
          <Button :disabled="loading" variant="ghost" size="icon" class="rounded-full" @click="refreshFiles">
            <IFluentArrowClockwise24Filled />
          </Button>
          <ButtonGroup>
            <Button :disabled="loading" :variant="viewMode === 'grid' ? 'default' : 'secondary'" size="icon" @click="viewMode = 'grid'">
              <IFluentGrid24Filled />
            </Button>
            <Button :disabled="loading" :variant="viewMode === 'list' ? 'default' : 'secondary'" size="icon" @click="viewMode = 'list'">
              <IFluentAppsList20Filled />
            </Button>
          </ButtonGroup>
        </div>
      </div>
      <div class="mt-4 flex items-center gap-2">
        <span class="text-xs text-zinc-600 dark:text-zinc-400">{{ $t("Filtrai") }}:</span>
        <FilterPopselect :disabled="loading" :options="[
          'Visi tipai',
          'Metodinė medžiaga',
          'Protokolai',
          'Veiklą reglamentuojantys dokumentai',
        ]" @select:value="contentTypeFilter = $event" />
      </div>
      <Separator class="my-4" />
      <FileViewer :results="results" :loading="loading" :view-mode="viewMode" :show-thumbnail="showThumbnail"
        :current-path="path" :starting-path="startingPath" />
      <FileDrawer :file="selectedFile" @hide:drawer="selectedFile = null" @file:deleted="handleFileDeleted" />
      <FileUploader :show="showFileUploader" :fileable="fileable" @close="handleFileUploaderClose" />
    </template>
    <p v-else v-once>
      Failų tvarkyklė išjungta, nes institucija nėra priskirta padaliniui.
    </p>
  </div>
</template>

<script setup lang="tsx">
import { Skeleton } from '@/Components/ui/skeleton';
import { computed, provide, ref, watch } from "vue";
import { useFetch, useStorage } from "@vueuse/core";

import { Button } from "@/Components/ui/button";
import { ButtonGroup } from "@/Components/ui/button-group";
import FileDrawer from "./FileDrawer.vue";
import FileUploader from "../Uploader/FileUploader.vue";
import FileViewer from "./FileGridTable.vue";
import FilterPopselect from "@/Components/Buttons/FilterPopselect.vue";
import FuzzySearcher from "./FuzzySearcher.vue";
import { Separator } from '@/Components/ui/separator';

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

  const { data, isFinished } = await useFetch(
    route("sharepoint.getDriveItems", { path: path })
  ).json();
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

const handleFileDeleted = (id: number) => {
  const index = rawFiles.value?.findIndex((file) => file.item.id === id);
  if (index !== -1) {
    rawFiles.value?.splice(index, 1);
  }
};
</script>
