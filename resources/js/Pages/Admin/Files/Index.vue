<template>
  <PageContent title="Failų tvarkyklė">
    <div>
      <div id="folders" class="main-card">
        <h2 class="text-2xl font-bold">
          Aplankai ({{ showedDirectories.length }})
        </h2>
        <div class="grid grid-cols-4 gap-3 lg:grid-cols-8 2xl:grid-cols-8">
          <FolderButton
            v-if="currentPath !== 'public/files'"
            @click="getAllFilesAndDirectories('../')"
          >
            <div class="mb-2">
              <NIcon size="32"><ArrowCircleLeft28Regular /></NIcon>
            </div>
            <div class="break-all text-center text-sm">Atgal</div>
          </FolderButton>
          <FolderButton
            v-for="directory in showedDirectories"
            :key="directory.id"
            @click="getAllFilesAndDirectories(directory.folderPath)"
          >
            <div class="mb-2">
              <NIcon size="32"><Folder48Regular /></NIcon>
            </div>
            <div class="break-all text-center text-sm">
              {{ directory.folderName }}
            </div>
          </FolderButton>
        </div>
      </div>
      <div
        v-if="showedFiles.length > 0"
        id="files"
        class="main-card max-h-full transition-all"
      >
        <h2 class="text-2xl font-bold">Failai ({{ showedFiles.length }})</h2>
        <transition-group
          tag="div"
          name="list"
          class="grid grid-cols-3 gap-3 lg:grid-cols-4 2xl:grid-cols-6"
        >
          <div class="h-40">
            <NUpload class="h-40 rounded-xl" @change="uploadFile">
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
              class="overflow-hidden text-ellipsis whitespace-pre-line break-all text-center text-sm"
            >
              {{ file.fileName }}
            </div>
          </FileButton>
        </transition-group>
      </div>
    </div>
  </PageContent>
</template>

<script lang="ts">
import AdminLayout from "@/Components/Layouts/AdminLayout.vue";

export default {
  layout: AdminLayout,
};
</script>

<script setup lang="ts">
import {
  ArrowCircleLeft28Regular,
  Folder48Regular,
  Image48Regular,
} from "@vicons/fluent";
import { Inertia } from "@inertiajs/inertia";
import { NIcon, NUpload, NUploadDragger } from "naive-ui";
import { computed } from "vue";

import FileButton from "@/Components/Buttons/FileButton.vue";
import FolderButton from "@/Components/Buttons/FolderButton.vue";
import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import route from "ziggy-js";

// Declare props
const props = defineProps<{
  directories: string[];
  files: string[];
  currentPath: string;
}>();

// Compute showed directories
const showedDirectories = computed(() => {
  const ar = [];
  props.directories.forEach((element, index) => {
    const folderName = element.split("/").slice(-1)[0];
    ar.push({ id: index, folderName: folderName, folderPath: element });
  });
  return ar;
});

// Compute showed files
const showedFiles = computed(() => {
  const ar = [];
  props.files.forEach((element, index) => {
    const fileName = element.split("/").slice(-1)[0];
    ar.push({ id: index, fileName: fileName, filePath: element });
  });
  return ar;
});

// Generate next path from button click
const getNextPath = (selectedDirectory) => {
  if (selectedDirectory === "../") {
    const arrayPath = props.currentPath.split("/");
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
  // truncate 'public'
  const fileName = filePath.substring(filePath.indexOf("/") + 1);
  window.open("/uploads/" + fileName, "_blank");
};

const uploadFile = (e) => {
  const file = e.file;
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
