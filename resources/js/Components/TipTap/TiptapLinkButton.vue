<template>
  <NButton size="small" @click="handleOpenModal">
    <template #icon>
      <IFluentLink20Regular />
    </template>
  </NButton>
  <CardModal v-model:show="showModal" class="max-w-3xl" title="Įkelti nuorodą" @close="showModal = false">
    <NTabs type="line">
      <NTabPane name="url" tab="Paprasta nuoroda">
        <div class="flex flex-col items-baseline gap-2">
          <NFormItem class="w-full" label="Nuoroda" :show-feedback="false">
            <NInput v-model:value="urlRef" placeholder="https://..." />
          </NFormItem>
          <NButton type="primary" @click="addLink">
            Įkelti
          </NButton>
        </div>
      </NTabPane>
      <NTabPane name="file" tab="Failas iš vusa.lt failų">
        <Suspense>
          <FileSelector v-if="showModal" @submit="addFileLink" />
          <div v-else class="h-32" />
          <template #fallback>
            <div class="flex h-32 items-center justify-center">
              <Spinner size="sm" />
            </div>
          </template>
        </Suspense>
      </NTabPane>
      <NTabPane name="archiveDocument" tab="Archyvo dokumentas">
        <Suspense>
          <ArchiveDocumentSelector v-if="showModal" @submit="addArchiveDocumentLink" />
        </Suspense>
      </NTabPane>
    </NTabs>
  </CardModal>
</template>

<script setup lang="ts">
import { ref } from "vue";

import CardModal from "../Modals/CardModal.vue";
import FileSelector from "@/Features/Admin/FileManager/FileSelector.vue";
import ArchiveDocumentSelector from "@/Features/Admin/ArchiveDocumentSelector.vue";
import { Spinner } from "@/Components/ui/spinner";

const props = defineProps<{
  editor?: any;
}>();

const emit = defineEmits<{
  (e: 'submit', url: string): void
  (e: 'document:submit', url: string): void
}>()

const showModal = ref(false);
const urlRef = ref("");

function handleOpenModal() {
  urlRef.value = props.editor?.getAttributes('link').href
  showModal.value = true;
}

function addLink() {

  if (typeof urlRef.value === 'string') {
    emit('submit', urlRef.value);
  }

  showModal.value = false;
}

function addFileLink(file: string) {
  if (file.startsWith("public")) {
    emit('submit', "/uploads/" + file.substring(file.indexOf("/") + 1));
  }
  emit('submit', file);
  showModal.value = false;
}

function addArchiveDocumentLink(url: string) {
  emit('document:submit', url);
  showModal.value = false;
}
</script>
