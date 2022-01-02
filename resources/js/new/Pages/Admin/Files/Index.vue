<template>
  <AdminLayout title="Failų tvarkyklė">
    <div id="files" class="pb-4">
      <h2 class="text-2xl my-4 font-bold">Aplankai ({{ showedDirectories.length }})</h2>
      <div class="grid grid-cols-3 gap-3 2xl:grid-cols-6 lg:grid-cols-4">
        <FileButton @click="getAllFilesAndDirectories('../')">
          <ArrowLeftIcon class="h-10 w-10 stroke-slate-600 mb-2" />
          <div class="text-sm break-all text-center">Atgal</div>
        </FileButton>
        <FileButton
          v-for="directory in showedDirectories"
          v-bind:key="directory.id"
          @click="getAllFilesAndDirectories(directory.folderPath)"
        >
          <FolderIcon class="h-10 w-10 stroke-slate-600 mb-2" />
          <div class="text-sm break-all text-center">
            {{ directory.folderName }}
          </div>
        </FileButton>
      </div>
    </div>
    <div id="files" v-if="showedFiles.length > 0">
      <h2 class="text-2xl my-4 font-bold">Failai ({{ showedFiles.length }})</h2>
      <div class="grid grid-cols-3 gap-3 2xl:grid-cols-6 lg:grid-cols-4">
        <FileButton v-for="file in showedFiles" v-bind:key="file.id">
          <PhotographIcon class="h-10 w-10 stroke-slate-600 mb-2" />
          <div class="text-sm break-all text-center">{{ file.fileName }}</div>
        </FileButton>
      </div>
    </div>
  </AdminLayout>
</template>

<script setup>
import AdminLayout from "@/Layouts/AdminLayout.vue";
import { NDataTable } from "naive-ui";
import { reactive, computed, onMounted } from "vue";
import { Inertia } from "@inertiajs/inertia";
import { Link } from "@inertiajs/inertia-vue3";
import {
  PhotographIcon,
  FolderIcon,
  ArrowLeftIcon,
} from "@heroicons/vue/outline";
import FileButton from "@/Components/Admin/FileButton.vue";

// Declare props
const props = defineProps({
  directories: Object,
  files: Object,
  currentPath: String,
});

// Compute showed directories
const showedDirectories = computed(() => {
  let ar = [];
  props.directories.forEach((element, index) => {
    let folderName = _.slice(_.split(element, "/"), -1)[0];
    ar.push({ id: index, folderName: folderName, folderPath: element });
  });
  return ar;
});

// Compute showed files
const showedFiles = computed(() => {
  let ar = [];
  props.files.forEach((element, index) => {
    let fileName = _.slice(_.split(element, "/"), -1)[0];
    ar.push({ id: index, fileName: fileName });
  });
  return ar;
});

// Generate next path from button click
const getNextPath = (selectedDirectory) => {
  if (selectedDirectory === "../") {
    let arrayPath = _.split(props.currentPath, "/");
    let selectedDirectory = "";
    arrayPath.pop();

    arrayPath.forEach((element) => {
      selectedDirectory += element + "/";
    });

    return selectedDirectory;
  } else return selectedDirectory;
};

// On directory button click, get all files and directories
const getAllFilesAndDirectories = async (selectedDirectory) => {
  await Inertia.reload({
    data: { currentPath: getNextPath(selectedDirectory) },
  });
};
</script>