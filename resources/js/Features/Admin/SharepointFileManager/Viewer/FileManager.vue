<template>
  <div
    class="mt-4 rounded-md border border-zinc-200 p-8 shadow-sm dark:border-zinc-50/10"
  >
    <div class="flex h-8">
      <div class="w-fit">
        <FuzzySearcher :data="files" @search:results="updateResults" />
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
    <div class="mt-4 flex">
      <FilterPopselect
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
      @select:file="handleFileSelect"
    />
    <FileDrawer
      :file="selectedFile"
      @hide:drawer="selectedFile = null"
    ></FileDrawer>
  </div>
</template>

<script setup lang="tsx">
import { AppsList20Filled, Grid20Filled } from "@vicons/fluent";
import { NButton, NButtonGroup, NDivider, NIcon } from "naive-ui";
import { computed, ref } from "vue";

import FileDrawer from "./FileDrawer.vue";
import FileViewer from "./FileGridTable.vue";
import FilterPopselect from "@/Components/Buttons/FilterPopselect.vue";
import FuzzySearcher from "./FuzzySearcher.vue";

// const emit = defineEmits<{
//   (event: "select:file", file: Record<string, any>): void;
// }>();

const props = defineProps<{
  files: MyDriveItem[];
}>();

const loading = ref(false);
const viewMode = ref("grid");
const selectedFile = ref<MyDriveItem | null>(null);
const contentTypeFilter = ref<string | null>(null);

const mapRawFiles = () => {
  return props.files.map((file, index) => {
    return {
      item: file,
      refIndex: index,
    };
  });
};

const rawFiles = mapRawFiles();
const searchResults = ref<Array<{
  item: MyDriveItem;
  refIndex: number;
}> | null>(null);

const updateResults = (searchItems) => {
  // if (searchResults?.value?.length === 0) {
  //   loading.value = false;
  //   console.log("No documents found.");
  //   return;
  // }

  console.log("Search results: ", searchItems);

  searchResults.value = searchItems;
};

const handleFileSelect = (file: MyDriveItem) => {
  selectedFile.value = file;
};

const results = computed(() => {
  let results = searchResults.value ?? rawFiles;

  // filter results by content type
  if (contentTypeFilter.value !== "Visi tipai" && contentTypeFilter.value) {
    results = results.filter((result) => {
      return (
        result.item.listItem?.fields?.additionalData?.Type ===
        contentTypeFilter.value
      );
    });
  }

  return results;
});
</script>
