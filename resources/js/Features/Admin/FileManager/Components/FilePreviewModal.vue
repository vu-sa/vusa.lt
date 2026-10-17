<template>
  <Dialog :open="current !== null" @update:open="(open) => !open && close()">
    <DialogContent
      :show-close-button="false"
      class="flex h-dvh max-h-dvh w-screen max-w-none flex-col gap-0 border-0 bg-background p-0 sm:max-w-none"
      data-slot="file-preview"
      @keydown.left.prevent="step(-1)"
      @keydown.right.prevent="step(1)"
    >
      <template v-if="current">
        <header class="flex items-center gap-3 border-b border-border px-3 py-2 sm:px-4">
          <div class="min-w-0 flex-1">
            <DialogTitle class="truncate text-sm font-semibold text-foreground">
              {{ current.name }}
            </DialogTitle>
            <DialogDescription class="truncate text-xs text-muted-foreground tabular-nums">
              <span v-if="files.length > 1">{{ index + 1 }} / {{ files.length }} · </span>
              <span class="uppercase">{{ getFileExtension(current.name) }}</span>
              · {{ formatBytes(current.size) }} · {{ formatDate(current.modified) }}
            </DialogDescription>
          </div>

          <div class="flex shrink-0 items-center gap-1">
            <Button variant="ghost" size="icon" :title="$t('files.ui.copy_link')" :aria-label="$t('files.ui.copy_link')" @click="emit('copyLink', current)">
              <Link2 class="size-4" aria-hidden="true" />
            </Button>
            <Button variant="ghost" size="icon" as="a" :href="originalUrl" :download="current.name" :title="$t('files.ui.download')" :aria-label="$t('files.ui.download')">
              <Download class="size-4" aria-hidden="true" />
            </Button>
            <Button
              variant="ghost"
              size="icon"
              as="a"
              :href="originalUrl"
              target="_blank"
              rel="noopener noreferrer"
              :title="$t('files.ui.open_original')"
              :aria-label="$t('files.ui.open_original')"
            >
              <ExternalLink class="size-4" aria-hidden="true" />
            </Button>
            <Button variant="ghost" size="icon" :title="$t('files.ui.details')" :aria-label="$t('files.ui.details')" @click="emit('details', current)">
              <Info class="size-4" aria-hidden="true" />
            </Button>
            <DialogClose as-child>
              <Button variant="outline" size="icon" :aria-label="$t('files.ui.close')">
                <X class="size-4" aria-hidden="true" />
              </Button>
            </DialogClose>
          </div>
        </header>

        <div class="relative flex min-h-0 flex-1 items-center justify-center bg-secondary/40 p-2 sm:p-6">
          <template v-if="kind === 'image'">
            <Spinner v-if="!loaded" class="absolute size-6 text-muted-foreground" />
            <img
              :key="current.path"
              :src="imageSrc(current.path, 1600)"
              :srcset
              sizes="100vw"
              :alt="current.name"
              :class="['max-h-full max-w-full object-contain transition-opacity', loaded ? 'opacity-100' : 'opacity-0']"
              @load="loaded = true"
              @error="loaded = true"
            >
          </template>
          <iframe
            v-else-if="kind === 'pdf'"
            :key="current.path"
            :src="originalUrl"
            :title="current.name"
            class="size-full border border-border bg-background"
          />
          <!-- eslint-disable-next-line vuejs-accessibility/media-has-caption -->
          <video v-else-if="kind === 'video'" :key="current.path" :src="originalUrl" controls class="max-h-full max-w-full" />
          <!-- eslint-disable-next-line vuejs-accessibility/media-has-caption -->
          <audio v-else-if="kind === 'audio'" :key="current.path" :src="originalUrl" controls class="w-full max-w-lg" />

          <template v-if="files.length > 1">
            <Button
              variant="outline"
              size="icon-lg"
              class="absolute top-1/2 left-2 -translate-y-1/2 bg-background/90 sm:left-4"
              :aria-label="$t('files.ui.previous')"
              @click="step(-1)"
            >
              <ChevronLeft class="size-5" aria-hidden="true" />
            </Button>
            <Button
              variant="outline"
              size="icon-lg"
              class="absolute top-1/2 right-2 -translate-y-1/2 bg-background/90 sm:right-4"
              :aria-label="$t('files.ui.next')"
              @click="step(1)"
            >
              <ChevronRight class="size-5" aria-hidden="true" />
            </Button>
          </template>
        </div>
      </template>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { trans as $t } from 'laravel-vue-i18n';
import { ChevronLeft, ChevronRight, Download, ExternalLink, Info, Link2, X } from '@lucide/vue';

import type { FileEntry } from '../types';
import { formatBytes, formatDate, imageSrc, isThumbnailable, previewKind, publicUrl } from '../utils';

import { Button } from '@/Components/ui/button';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogTitle } from '@/Components/ui/dialog';
import { Spinner } from '@/Components/ui/spinner';
import { getFileExtension } from '@/Utils/fileIcons';

const props = defineProps<{
  /** The previewable files the arrows step through, in the order the list shows them. */
  files: FileEntry[];
  /** Path of the file on screen; null closes the preview. */
  modelValue: string | null;
}>();

const emit = defineEmits<{
  'update:modelValue': [path: string | null];
  'details': [file: FileEntry];
  'copyLink': [file: FileEntry];
}>();

const loaded = ref(false);

const index = computed(() => props.files.findIndex(file => file.path === props.modelValue));
const current = computed(() => (index.value >= 0 ? props.files[index.value] : null));
const kind = computed(() => (current.value ? previewKind(current.value.name) : null));
const originalUrl = computed(() => (current.value ? publicUrl(current.value.path) : ''));

/** The server never upscales, so the 2400 candidate only costs more where the original is that large. */
const srcset = computed(() => {
  if (!current.value || !isThumbnailable(current.value.name)) return undefined;
  return `${imageSrc(current.value.path, 1600)} 1600w, ${imageSrc(current.value.path, 2400)} 2400w`;
});

watch(() => props.modelValue, () => {
  loaded.value = false;
});

function step(delta: number) {
  if (props.files.length < 2 || index.value < 0) return;
  const next = (index.value + delta + props.files.length) % props.files.length;
  emit('update:modelValue', props.files[next].path);
}

function close() {
  emit('update:modelValue', null);
}
</script>
