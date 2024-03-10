<template>
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
import { NSpin } from 'naive-ui';

import CardModal from '../Modals/CardModal.vue';
import FileSelector from '@/Features/Admin/FileManager/FileSelector.vue';

const showModal = defineModel<boolean>('showModal');

const emit = defineEmits<{
  (e: 'submit', url: string): void
}>()

function addImage(url: string) {
  // change from /public to /uploads
  emit('submit', "/uploads/" + url.substring(url.indexOf("/") + 1));
  showModal.value = false;
}
</script>
