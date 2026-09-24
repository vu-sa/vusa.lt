<template>
  <Teleport to="body">
    <Transition name="fade">
      <div
        v-if="file"
        class="fixed inset-0 z-[100] flex items-center justify-center bg-background/90 backdrop-blur-xs p-4"
        role="dialog"
        aria-modal="true"
        :aria-label="file.name"
      >
        <!-- Backdrop Button -->
        <button
          type="button"
          class="absolute inset-0 size-full cursor-default bg-transparent -z-10"
          :aria-label="$t('Uždaryti')"
          tabindex="-1"
          @click="$emit('close')"
        />

        <!-- Close Button -->
        <button
          type="button"
          class="absolute right-4 top-4 flex size-10 items-center justify-center border border-border bg-background text-foreground transition-colors hover:bg-secondary z-10"
          :aria-label="$t('Uždaryti')"
          @click="$emit('close')"
        >
          <X class="size-5" aria-hidden="true" />
        </button>

        <!-- Container -->
        <div class="flex max-h-full max-w-4xl flex-col items-center">
          <img
            :src="previewSrc"
            :alt="file.name"
            class="max-h-[80vh] max-w-full border border-border object-contain bg-secondary/30"
          >

          <!-- Caption bar -->
          <div class="mt-3 flex w-full items-center justify-between gap-4 text-foreground">
            <div class="min-w-0 flex-1">
              <p class="truncate text-sm font-bold text-foreground">
                {{ file.name }}
              </p>
              <p class="text-xs text-muted-foreground">
                <span v-if="file.dimensions">{{ file.dimensions }} · </span>
                <span>{{ formatBytes(file.size) }}</span>
              </p>
            </div>

            <div class="flex items-center gap-2">
              <a
                :href="originalSrc"
                target="_blank"
                rel="noopener noreferrer"
                download
                class="inline-flex min-h-8 items-center gap-1.5 border border-border bg-secondary/50 px-3 text-xs font-semibold text-foreground transition-colors hover:bg-secondary"
              >
                <Download class="size-3.5" aria-hidden="true" />
                <span>{{ $t('Atsisiųsti') }}</span>
              </a>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup lang="ts">
import { computed, onMounted, onUnmounted } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { Download, X } from 'lucide-vue-next';

import type { FileEntry } from '../types';
import { formatBytes } from '../utils';

const props = defineProps<{
  file: FileEntry | null;
}>();

const emit = defineEmits<{
  close: [];
}>();

const originalSrc = computed(() => {
  if (!props.file) return '';
  return `/uploads/${props.file.path.replace(/^public\//, '')}`;
});

const previewSrc = computed(() => {
  if (!props.file) return '';
  return route('api.v1.admin.files.thumbnail', { path: props.file.path, w: 1200 });
});

function handleKeydown(e: KeyboardEvent) {
  if (e.key === 'Escape') {
    emit('close');
  }
}

onMounted(() => {
  window.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
  window.removeEventListener('keydown', handleKeydown);
});
</script>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}
.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}
</style>
