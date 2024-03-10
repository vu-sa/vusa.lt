<template>
  <NButton size="small" @click="handleModalOpen">
    <template #icon>
      <NIcon :component="Image20Regular" />
    </template>
  </NButton>
  <CardModal v-model:show="showModal" class="max-w-3xl" title="Pasirinkti paveikslėlį" @close="showModal = false">
    <Suspense>
      <FileSelector v-if="showModal" :file-extensions="['jpg', 'jpeg', 'png', 'gif']" @submit="addImage" />
      <div v-else class="h-32" />
      <template #fallback>
        <div class="flex h-32 items-center justify-center">
          <NSpin />
        </div>
      </template>
    </Suspense>
  </CardModal>
</template>

<script setup lang="ts">
import { Image20Regular } from "@vicons/fluent";
import { NButton, NIcon, NSpin } from "naive-ui";
import { ref } from "vue";

import CardModal from "../Modals/CardModal.vue";
import FileSelector from "@/Features/Admin/FileManager/FileSelector.vue";

const emit = defineEmits<{
  (e: 'submit', url: string): void
}>()

const showModal = ref(false);

async function handleModalOpen() {
  showModal.value = true;
}

function addImage(url: string) {
  // change from /public to /uploads
  emit('submit', "/uploads/" + url.substring(url.indexOf("/") + 1));
  showModal.value = false;
}
</script>
