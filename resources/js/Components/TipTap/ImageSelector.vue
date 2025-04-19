<template>
  <CardModal v-model:show="showModal" class="max-w-3xl" title="Pasirinkti paveikslėlį" @close="showModal = false">
    <Suspense>
      <FileSelector v-if="showModal" :file-extensions @submit="addImage" />
      <div v-else class="h-32" />
      <template #fallback>
        <div class="flex h-32 items-center justify-center">
          <Spinner size="sm" />
        </div>
      </template>
    </Suspense>
  </CardModal>
</template>
<script setup lang="ts">
import { computed } from 'vue';

import { Spinner } from '@/Components/ui/spinner';
import CardModal from '../Modals/CardModal.vue';
import FileSelector from '@/Features/Admin/FileManager/FileSelector.vue';

const props = defineProps<{
  // only specific values for selectionType are allowed
  selectionType?: 'image' | 'video';
}>()

const showModal = defineModel<boolean>('showModal');

const fileExtensions = computed(() => {
  if (props.selectionType === 'video') {
    return ['mp4', 'webm', 'ogg', 'MP4', 'WEBM', 'OGG'];
  }

    return ['jpg', 'jpeg', 'png', 'gif', 'JPG', 'PNG', 'webp', 'GIF', 'WEBP', 'JPEG', 'svg', 'SVG'];
});

const emit = defineEmits<{
  (e: 'submit', url: string): void
}>()

function addImage(url: string) {
  // change from /public to /uploads
  emit('submit', "/uploads/" + url.substring(url.indexOf("/") + 1));
  showModal.value = false;
}
</script>
