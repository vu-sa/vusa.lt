<template>
  <div>
    <div class="grid grid-cols-[auto__1fr] items-center gap-x-6 gap-y-4 max-sm:grid-cols-2">
      <NButtonGroup>
        <!-- Add file or folder   -->
        <NButton size="small" @click="showFileUploadModal = true">
          <template #icon>
            <IFluentDocumentAdd24Regular />
          </template>
          Pridėti failą
        </NButton>
        <NButton size="small" @click="showFolderUploadModal = true">
          <template #icon>
            <IFluentFolderAdd24Regular />
          </template>
          Pridėti aplanką
        </NButton>
      </NButtonGroup>
      <NInput v-model:value="search" style="width: 300px; margin-left: auto;" :size="small ? 'small' : 'medium'" round
        placeholder="Filtruoti..." />

      <div class="col-span-2 text-zinc-500">
        {{ shownPath }}
      </div>
    </div>
    <div class="mt-4 flex flex-wrap gap-4 rounded-md border border-zinc-200 p-8 shadow-xs dark:border-zinc-50/10">
      <FileButton v-if="props.path !== 'public/files'" :key="'back'" :small
        class="dark:from-zinc-800/90 dark:to-zinc-700/90" :icon-string="'folder'" :name="'..'" @dblclick="handleBack" />
      <FileButton v-for="folder in shownDirectories" :key="folder.id" :small
        class="dark:from-zinc-800/90 dark:to-zinc-700/90" :icon-string="'folder'" :name="folder.name"
        @dblclick="handleChangeDirectory(folder.path)" />
      <div v-for="file in shownFiles" :key="file.id" class="group relative">
        <FileButton :small class="dark:from-zinc-800/90 dark:to-zinc-700/90"
          :icon-string="getIconString(file.name, false)" :name="file.name"
          :show-thumbnail="file?.name?.endsWith('.jpg') || file?.name?.endsWith('.png') || file?.name?.endsWith('.jpeg')"
          :thumbnail="`/uploads/${file.path?.substring(file.path.indexOf('/') + 1)}`"
          @dblclick="$emit('fileSelected', file.path)" />
        <NButton class="-right-2 -top-2 opacity-0 group-hover:opacity-100" style="position: absolute" size="small"
          type="error" circle @click="deleteFile(file.path)">
          <template #icon>
            <IFluentDelete24Filled />
          </template>
        </NButton>
      </div>
    </div>
    <CardModal :show="showFileUploadModal" title="Pridėti failą" @close="showFileUploadModal = false">
      <NSpin :show="loading">
        <NUpload ref="upload" v-model:file-list="fileList" multiple class="rounded-xl">
          <NUploadDragger style="height: 100%">
            <div style="margin-bottom: 12px">
              <IFluentArchive24Regular width="48" height="48" class="mx-auto" />
            </div>
            <p style="font-size: 16px">
              Paspausk arba įtempk failą
            </p>
          </NUploadDragger>
        </NUpload>
        <NButton type="primary" style="margin-top: 1rem" @click="uploadFiles">
          Įkelti
        </NButton>
      </NSpin>
    </CardModal>
    <CardModal :show="showFolderUploadModal" title="Pridėti aplanką" @close="showFolderUploadModal = false">
      <div>
        <NFormItem label="Naujo aplanko pavadinimas">
          <NInput v-model:value="newFolderName" placeholder="Pavadinimas..." />
        </NFormItem>
        <NButton :loading="loading" type="primary" @click="createDirectory">
          Sukurti
        </NButton>
      </div>
    </CardModal>
    <CardModal :show="showDeleteModal" title="Ištrinti failą" @close="showDeleteModal = false">
      <div>
        <p class="mb-4 text-base font-bold">
          Ar tikrai nori ištrinti šį failą? Failo bus neįmanoma atkurti!
        </p>
        <p class="mb-4">
          Prieš ištrinant failą, įsitikink, kad jis nėra naudojamas jokiame puslapyje.
        </p>
        <p class="mb-4 text-zinc-500">
          {{ selectedFileForDeletion }}
        </p>
        <NButtonGroup>
          <NButton :loading="loading" type="error" @click="deleteFileConfirmed">
            Taip
          </NButton>
          <NButton @click="showDeleteModal = false">
            Ne
          </NButton>
        </NButtonGroup>
      </div>
    </CardModal>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { useMessage } from 'naive-ui';

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
  update: [path: string],
}>()

const message = useMessage();
const fileList = ref([]);

const showFileUploadModal = ref(false);
const showFolderUploadModal = ref(false);
const showDeleteModal = ref(false);

const selectedFileForDeletion = ref("");
const newFolderName = ref("");

const loading = ref(false);

const search = ref("");

// Split path with public/files and return only the path
const shownPath = computed(() => {

  // Check if path has public/files
  if (props.path === "public/files") {
    return "/";
  }

  if (props.path.includes("public/files")) {
    return props.path.split("public/files")[1];
  }

  return props.path;
});

const shownFiles = computed(() => {
  if (search.value === "") {
    return props.files;
  }

  return props.files.filter((file: any) => {
    return file.name.toLowerCase().includes(search.value.toLowerCase());
  });
});

const shownDirectories = computed(() => {
  if (search.value === "") {
    return props.directories;
  }

  return props.directories.filter((directory: any) => {
    return directory.name.toLowerCase().includes(search.value.toLowerCase());
  });
});

const uploadFiles = () => {
  loading.value = true;
  router.post(
    route("files.store"),
    { files: fileList.value, path: props.path },
    {
      preserveScroll: true,
      preserveState: true,
      onSuccess: () => {
        message.success("Failai įkelti");
        showFileUploadModal.value = false;
        loading.value = false;
        emit("update", props.path);
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
        emit("update", props.path);
      },
    },
  );
};

const deleteFile = (path: string) => {
  selectedFileForDeletion.value = path;
  showDeleteModal.value = true;
};

const deleteFileConfirmed = () => {
  loading.value = true;
  router.delete(
    route("files.delete", { path: selectedFileForDeletion.value }),
    {
      preserveScroll: true,
      preserveState: true,
      onSuccess: () => {
        message.success("Failas ištrintas");
        loading.value = false;
        showDeleteModal.value = false;
        emit("update", props.path);
      },
    },
  );
};

function handleChangeDirectory(path: string) {
  emit("changeDirectory", path);
}

function handleBack() {
  emit("back");
}

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
