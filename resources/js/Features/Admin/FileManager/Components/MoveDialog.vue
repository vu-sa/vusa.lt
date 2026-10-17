<template>
  <Dialog open @update:open="(open) => !open && $emit('close')">
    <DialogContent class="flex max-h-[85dvh] flex-col rounded-none sm:max-w-lg">
      <DialogHeader>
        <DialogTitle>{{ $t('files.ui.move_title', { count: String(paths.length) }) }}</DialogTitle>
        <DialogDescription>{{ $t('files.ui.move_description') }}</DialogDescription>
      </DialogHeader>

      <nav :aria-label="$t('files.ui.path')" class="flex flex-wrap items-center gap-0.5 border-y border-border py-1 text-sm">
        <PathBreadcrumb
          :crumbs
          :root-label="$t('files.ui.root')"
          :root-path="ROOT_PATH"
          @navigate="fetch"
        />
      </nav>

      <div class="min-h-40 flex-1 overflow-y-auto">
        <div v-if="loading" class="space-y-2">
          <Skeleton v-for="i in 4" :key="i" class="h-11 w-full rounded-none" />
        </div>
        <p v-else-if="directoriesRaw.length === 0" class="py-6 text-center text-sm text-muted-foreground">
          {{ $t('files.ui.no_subfolders') }}
        </p>
        <ul v-else class="divide-y divide-border border border-border">
          <li v-for="directory in directoriesRaw" :key="directory.path">
            <button
              type="button"
              class="flex min-h-11 w-full items-center gap-2.5 px-3 text-left text-sm hover:bg-secondary focus-visible:bg-secondary focus-visible:outline-none"
              @click="fetch(directory.path)"
            >
              <Folder class="size-4 shrink-0 text-brand" aria-hidden="true" />
              <span class="flex-1 truncate">{{ directory.name }}</span>
              <ChevronRight class="size-4 text-muted-foreground" aria-hidden="true" />
            </button>
          </li>
        </ul>
      </div>

      <div class="border-l-2 border-status-attention bg-status-attention-surface p-3 text-xs text-foreground">
        {{ $t('files.ui.move_warning') }}
      </div>

      <DialogFooter class="items-center gap-2 sm:justify-between">
        <span class="truncate text-xs text-muted-foreground">
          {{ isSourceFolder ? $t('files.ui.move_same_folder') : $t('files.ui.move_destination', { folder: destinationLabel }) }}
        </span>
        <div class="flex gap-2">
          <Button type="button" variant="outline" voice="sentence" @click="$emit('close')">
            {{ $t('files.ui.cancel') }}
          </Button>
          <Button variant="brand" :disabled="processing || loading || isSourceFolder" @click="submit">
            <Spinner v-if="processing" class="size-4" />
            {{ $t('files.ui.move_here') }}
          </Button>
        </div>
      </DialogFooter>
    </DialogContent>
  </Dialog>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { ChevronRight, Folder } from '@lucide/vue';

import { useFileListing } from '../useFileListing';
import { relativeToRoot, ROOT_PATH } from '../utils';

import PathBreadcrumb from './PathBreadcrumb.vue';

import { Button } from '@/Components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/Components/ui/dialog';
import { Skeleton } from '@/Components/ui/skeleton';
import { Spinner } from '@/Components/ui/spinner';

const props = defineProps<{
  paths: string[];
  /** The folder the files are in now; browsing starts here. */
  from: string;
}>();

const emit = defineEmits<{
  close: [];
  moved: [destination: string];
}>();

// No file types match "-", so the listing carries folders only.
const { directoriesRaw, currentPath, loading, fetch } = useFileListing(props.from, ['-']);

const processing = ref(false);

const isSourceFolder = computed(() => props.paths.every(path => path.split('/').slice(0, -1).join('/') === currentPath.value));
const destinationLabel = computed(() => relativeToRoot(currentPath.value) || $t('files.ui.root'));

const crumbs = computed(() => {
  const parts = relativeToRoot(currentPath.value).split('/').filter(Boolean);
  return parts.map((part, index) => ({ name: part, path: `${ROOT_PATH}/${parts.slice(0, index + 1).join('/')}` }));
});

function submit() {
  if (processing.value || isSourceFolder.value) return;

  processing.value = true;
  const destination = currentPath.value;

  router.post(route('files.move'), { paths: props.paths, destination }, {
    preserveScroll: true,
    preserveState: true,
    onSuccess: () => emit('moved', destination),
    onFinish: () => {
      processing.value = false;
    },
  });
}
</script>
