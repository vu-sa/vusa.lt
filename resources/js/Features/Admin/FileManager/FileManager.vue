<template>
  <div>
    <NButtonGroup>
      <!-- Add file or folder   -->
      <NButton size="small" @click="showFileUploadModal = true">
        <template #icon>
          <NIcon :component="DocumentAdd24Regular" />
        </template>
        Pridėti failą
      </NButton>
      <NButton size="small" @click="showFolderUploadModal = true">
        <template #icon>
          <NIcon :component="FolderAdd24Regular" />
        </template>
        Pridėti aplanką
      </NButton>
    </NButtonGroup>
    <div class="mt-4 flex flex-wrap gap-4 rounded-md border border-zinc-200 p-8 shadow-sm dark:border-zinc-50/10">
      <FileButton v-if="props.path !== 'public/files'" :key="'back'" :small
        class="dark:from-zinc-800/90 dark:to-zinc-700/90" :icon-string="'folder'" :name="'..'"
        @dblclick="$emit('back')" />
      <FileButton v-for="folder in directories" :key="folder.id" :small
        class="dark:from-zinc-800/90 dark:to-zinc-700/90" :icon-string="'folder'" :name="folder.name"
        @dblclick="$emit('changeDirectory', folder.path)" />
      <FileButton v-for="file in files" :key="file.id" :small class="dark:from-zinc-800/90 dark:to-zinc-700/90"
        :icon-string="getIconString(file.name, false)" :name="file.name"
        :show-thumbnail="file.name.endsWith('.jpg') || file.name.endsWith('.png')"
        :thumbnail="`/uploads/${file.path.substring(file.path.indexOf('/') + 1)}`" @dblclick="$emit('fileSelected', file.path)" />
    </div>
    <CardModal :show="showFileUploadModal" title="Pridėti failą" @close="showFileUploadModal = false">
      <NUpload :show-file-list="false" class="h-40 rounded-xl" @change="uploadFile">
        <NUploadDragger style="height: 100%">
          <div style="margin-bottom: 12px">
            <NIcon size="48" :depth="3" :component="Archive" />
          </div>
          <p style="font-size: 16px">
            Paspausk arba įtempk failą
          </p>
        </NUploadDragger>
      </NUpload>
    </CardModal>
    <CardModal :show="showFolderUploadModal" title="Pridėti aplanką" @close="showFolderUploadModal = false">
      <div>
        <NFormItem label="Naujo aplanko pavadinimas">
          <NInput v-model:value="newFolderName" placeholder="Pavadinimas..." />
        </NFormItem>
        <NButton :loading="loading" type="primary" @click="createDirectory">Sukurti</NButton>
      </div>
    </CardModal>
  </div>
</template>

<script setup lang="ts">
import { Archive } from '@vicons/fa';
import { DocumentAdd24Regular, FolderAdd24Regular } from '@vicons/fluent';
import { NButton, NButtonGroup, NFormItem, NIcon, NInput, NUpload, NUploadDragger, useMessage } from 'naive-ui';
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';

import CardModal from '@/Components/Modals/CardModal.vue';
import FileButton from '../SharepointFileManager/Viewer/FileButton.vue';

const props = defineProps<{
  directories: any;
  files: any;
  path: string;
  small?: boolean;
}>()

const emit = defineEmits<{
  back: [],
  changeDirectory: [directory: string],
  fileSelected: [file: string],
  update: [],
}>()

const message = useMessage();

const showFileUploadModal = ref(false);
const showFolderUploadModal = ref(false);
const loading = ref(false);

const newFolderName = ref("");

const uploadFile = (e) => {
  const file = e.file;
  router.post(
    route("files.store"),
    { file, path: props.path },
    {
      preserveScroll: true,
      preserveState: true,
      onSuccess: () => {
        message.success("Failas įkeltas");
        showFileUploadModal.value = false;
        emit("update");
      },
    },
  );
};

const createDirectory = () => {
  loading.value = true;

  router.post(
    route("files.createDirectory"),
    { path: props.path, name: newFolderName.value },
    {
      preserveScroll: true,
      preserveState: true,
      onSuccess: () => {
        message.success("Aplankas sukurtas");
        showFolderUploadModal.value = false;
        loading.value = false;
        emit("update");
      },
    },
  );
};

function getIconString(file: string, isFolder: boolean | undefined) {
  if (isFolder) {
    return "folder";
  }

  if (
    file.endsWith(".doc") ||
    file.endsWith(".docx") ||
    file.endsWith(".odt")
  ) {
    return "file-word";
  }

  if (file.endsWith(".xls") || file.endsWith(".xlsx")) {
    return "file-excel";
  }

  if (file.endsWith(".pdf")) {
    return "file-pdf";
  }

  return "file";
}
</script>
