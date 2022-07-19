<template>
  <AdminLayout title="Failų tvarkyklė">
    <div id="folders" class="main-card">
      <h2 class="text-2xl font-bold">
        Aplankai ({{ showedDirectories.length }})
      </h2>
      <div class="grid grid-cols-3 gap-3 2xl:grid-cols-6 lg:grid-cols-4">
        <FolderButton
          v-if="currentPath !== 'public/files'"
          @click="getAllFilesAndDirectories('../')"
        >
          <div class="mb-2">
            <NIcon size="32"><ArrowCircleLeft28Regular /></NIcon>
          </div>
          <div class="text-sm break-all text-center">Atgal</div>
        </FolderButton>
        <FolderButton
          v-for="directory in showedDirectories"
          :key="directory.id"
          @click="getAllFilesAndDirectories(directory.folderPath)"
        >
          <div class="mb-2">
            <NIcon size="32"><Folder48Regular /></NIcon>
          </div>
          <div class="text-sm break-all text-center">
            {{ directory.folderName }}
          </div>
        </FolderButton>
      </div>
    </div>
    <div
      v-if="showedFiles.length > 0"
      id="files"
      class="main-card transition-all max-h-full"
    >
      <h2 class="text-2xl font-bold">Failai ({{ showedFiles.length }})</h2>
      <transition-group
        tag="div"
        name="list"
        class="grid grid-cols-3 gap-3 2xl:grid-cols-6 lg:grid-cols-4"
      >
        <div class="h-40">
          <NUpload class="rounded-xl h-40" @change="uploadFile">
            <NUploadDragger style="height: 100%">
              <div style="margin-bottom: 12px">
                <!-- <n-icon size="48" :depth="3">
                  <archive-icon />
                </n-icon> -->
              </div>
              <p style="font-size: 16px">Paspausk arba įtempk failą</p>
            </NUploadDragger>
          </NUpload>
        </div>
        <FileButton
          v-for="file in showedFiles"
          :key="file.id"
          @click="openFile(file.filePath)"
        >
          <div class="mb-2">
            <NIcon size="32"><Image48Regular /></NIcon>
          </div>
          <div
            class="text-sm text-center text-ellipsis overflow-hidden whitespace-pre-line break-all"
          >
            {{ file.fileName }}
          </div>
        </FileButton>
      </transition-group>
    </div>
  </AdminLayout>
</template>

<script setup>
import {
  ArrowCircleLeft28Regular,
  Folder48Regular,
  Image48Regular,
} from "@vicons/fluent";
import { Inertia } from "@inertiajs/inertia";
import { NIcon, NUpload, NUploadDragger } from "naive-ui";
import { computed } from "vue";
import AdminLayout from "@/Layouts/AdminLayout.vue";
import FileButton from "@/Components/Admin/FileButton.vue";
import FolderButton from "@/Components/Admin/FolderButton.vue";

// Declare props
const props = defineProps({
  directories: Object,
  files: Object,
  currentPath: String,
});

// const { message } = createDiscreteApi(["message"]);

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
    ar.push({ id: index, fileName: fileName, filePath: element });
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

    return selectedDirectory.slice(0, -1);
  } else return selectedDirectory;
};

// On directory button click, get all files and directories
const getAllFilesAndDirectories = async (selectedDirectory) => {
  await Inertia.reload({
    data: { currentPath: getNextPath(selectedDirectory) },
  });
};

const openFile = (filePath) => {
  console.log(filePath);
  // truncate 'public'
  let fileName = filePath.substring(filePath.indexOf("/") + 1);
  // console.log(fileName);
  window.open("/uploads/" + fileName, "_blank");
};

const uploadFile = (e) => {
  let file = e.file;
  Inertia.post(
    route("files.store"),
    { file, path: props.currentPath },
    {
      preserveScroll: true,
      preserveState: true,
      onSuccess: () => {
        // message.success("Failas įkeltas");
      },
    }
  );
};
</script>

<style>
.list-enter-active,
.list-leave-active {
  transition: all 1s ease;
}

.list-enter-from,
.list-leave-to {
  opacity: 0;
  transform: translateY(30px);
}
</style>
