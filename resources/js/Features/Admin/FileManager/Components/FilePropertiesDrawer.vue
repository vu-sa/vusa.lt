<template>
  <Sheet :open="file !== null" @update:open="(open) => !open && $emit('close')">
    <SheetContent
      :side="isMobile ? 'bottom' : 'right'"
      :class="[
        'flex flex-col gap-0 p-0',
        isMobile ? 'max-h-[92dvh]' : 'w-full sm:max-w-sm',
      ]"
      data-slot="file-details"
    >
      <template v-if="file">
        <SheetHeader class="border-b border-border px-4 py-3 pr-12">
          <p class="text-[11px] font-bold uppercase tracking-[0.2em] text-muted-foreground">
            {{ $t('files.ui.details') }}
          </p>
          <SheetTitle class="text-base font-semibold break-words text-foreground">
            {{ file.name }}
          </SheetTitle>
          <SheetDescription class="text-xs text-muted-foreground">
            {{ location }}
          </SheetDescription>
        </SheetHeader>

        <div class="flex-1 space-y-4 overflow-y-auto p-4">
          <button
            type="button"
            class="block w-full outline-none focus-visible:ring-[3px] focus-visible:ring-ring/40 disabled:cursor-default"
            :disabled="!canPreview"
            :aria-label="$t('files.ui.preview_name', { name: file.name })"
            @click="$emit('preview')"
          >
            <FileThumb
              :path="file.path"
              :name="file.name"
              :width="640"
              fit="contain"
              icon-class="size-12 text-muted-foreground"
              class="aspect-[4/3] w-full border border-border"
            />
          </button>

          <Button
            v-if="selectionMode"
            variant="brand"
            class="w-full"
            @click="$emit('insert')"
          >
            <Link2 class="size-4" aria-hidden="true" />
            {{ $t('files.ui.insert') }}
          </Button>

          <dl class="grid grid-cols-[auto_minmax(0,1fr)] gap-x-4 gap-y-2 border-y border-border py-3 text-xs">
            <dt class="text-muted-foreground">
              {{ $t('files.ui.type') }}
            </dt>
            <dd class="text-right font-medium uppercase text-foreground">
              {{ extension || '—' }}
            </dd>
            <dt class="text-muted-foreground">
              {{ $t('files.ui.size') }}
            </dt>
            <dd class="text-right font-medium text-foreground tabular-nums">
              {{ formatBytes(file.size) }}
            </dd>
            <dt class="text-muted-foreground">
              {{ $t('files.ui.modified') }}
            </dt>
            <dd class="text-right font-medium text-foreground">
              {{ formatDate(file.modified) }}
            </dd>
            <template v-if="dimensions">
              <dt class="text-muted-foreground">
                {{ $t('files.ui.dimensions') }}
              </dt>
              <dd class="text-right font-medium text-foreground tabular-nums">
                {{ dimensions }}
              </dd>
            </template>
          </dl>

          <div class="grid grid-cols-2 gap-2">
            <Button v-if="canPreview" variant="outline" voice="sentence" @click="$emit('preview')">
              <Eye class="size-4" aria-hidden="true" />
              {{ $t('files.ui.preview') }}
            </Button>
            <Button variant="outline" voice="sentence" @click="$emit('copyLink')">
              <Link2 class="size-4" aria-hidden="true" />
              {{ $t('files.ui.copy_link') }}
            </Button>
            <Button variant="outline" voice="sentence" as="a" :href="originalUrl" :download="file.name">
              <Download class="size-4" aria-hidden="true" />
              {{ $t('files.ui.download') }}
            </Button>
            <Button variant="outline" voice="sentence" @click="$emit('toggleStar')">
              <Star :class="['size-4', isStarred && 'fill-status-attention text-status-attention']" aria-hidden="true" />
              {{ isStarred ? $t('files.ui.unstar') : $t('files.ui.star') }}
            </Button>
            <template v-if="!selectionMode">
              <Button v-if="abilities.update" variant="outline" voice="sentence" @click="$emit('rename')">
                <PencilLine class="size-4" aria-hidden="true" />
                {{ $t('files.ui.rename') }}
              </Button>
              <Button v-if="abilities.update" variant="outline" voice="sentence" @click="$emit('move')">
                <FolderInput class="size-4" aria-hidden="true" />
                {{ $t('files.ui.move') }}
              </Button>
              <Button variant="outline" voice="sentence" :disabled="usage.scanning.value" @click="usage.scan(file.path)">
                <Spinner v-if="usage.scanning.value" class="size-4" />
                <SearchCheck v-else class="size-4" aria-hidden="true" />
                {{ $t('files.ui.check_usage') }}
              </Button>
              <Button
                v-if="showCompress"
                variant="outline"
                voice="sentence"
                :disabled="compressing"
                :title="compressing ? $t('files.optimize.progress') : $t('files.optimize.action')"
                @click="pendingCompressionPath = file.path"
              >
                <Spinner v-if="compressing" class="size-4" />
                <ImageDown v-else class="size-4" aria-hidden="true" />
                {{ $t('files.ui.optimize') }}
              </Button>
            </template>
          </div>

          <FileUsageList v-if="usage.result.value" :usage="usage.result.value" />

          <Button
            v-if="!selectionMode && abilities.delete"
            variant="outline"
            voice="sentence"
            class="w-full text-destructive hover:border-destructive hover:text-destructive"
            @click="$emit('delete')"
          >
            <Trash2 class="size-4" aria-hidden="true" />
            {{ $t('files.ui.delete') }}
          </Button>
        </div>
      </template>
    </SheetContent>
  </Sheet>
  <ConfirmDialog
    :open="pendingCompressionPath !== null"
    :title="$t('files.optimize.dialog_title')"
    :description="$t('files.optimize.dialog_description')"
    :confirm-label="$t('files.optimize.action')"
    @update:open="(open) => !open && (pendingCompressionPath = null)"
    @confirm="confirmCompression"
  />
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import {
  Download,
  Eye,
  FolderInput,
  ImageDown,
  Link2,
  PencilLine,
  SearchCheck,
  Star,
  Trash2,
} from '@lucide/vue';

import type { FileEntry, FileManagerAbilities } from '../types';
import { useFileUsageScan } from '../useFileUsageScan';
import { formatBytes, formatDate, isPreviewableImage, previewKind, publicUrl, relativeToRoot } from '../utils';

import FileThumb from './FileThumb.vue';
import FileUsageList from './FileUsageList.vue';

import ConfirmDialog from '@/Components/Patterns/ConfirmDialog.vue';
import { Button } from '@/Components/ui/button';
import { Sheet, SheetContent, SheetDescription, SheetHeader, SheetTitle } from '@/Components/ui/sheet';
import { Spinner } from '@/Components/ui/spinner';
import { useIsMobile } from '@/Composables/useIsMobile';
import { getFileExtension } from '@/Utils/fileIcons';

const props = withDefaults(defineProps<{
  file: FileEntry | null;
  selectionMode?: boolean;
  isStarred?: boolean;
  abilities?: Pick<FileManagerAbilities, 'update' | 'delete'>;
}>(), {
  abilities: () => ({ update: true, delete: true }),
});

defineEmits<{
  close: [];
  delete: [];
  preview: [];
  toggleStar: [];
  insert: [];
  rename: [];
  move: [];
  copyLink: [];
}>();

const isMobile = useIsMobile();
const usage = useFileUsageScan();

const compressing = ref(false);
const pendingCompressionPath = ref<string | null>(null);
const dimensions = ref<string | null>(null);

const extension = computed(() => (props.file ? getFileExtension(props.file.name) : ''));
const originalUrl = computed(() => (props.file ? publicUrl(props.file.path) : ''));
const canPreview = computed(() => !!props.file && previewKind(props.file.name) !== null);
const location = computed(() => {
  const directory = props.file?.directory ?? props.file?.path.split('/').slice(0, -1).join('/') ?? '';
  return `${$t('files.ui.root')}/${relativeToRoot(directory)}`.replace(/\/$/, '');
});

const OPTIMISABLE_EXTENSIONS = ['jpg', 'jpeg', 'png'];

const showCompress = computed(() => !!props.file
  && props.abilities.update
  && OPTIMISABLE_EXTENSIONS.includes(extension.value)
  && props.file.size > 500 * 1024);

/** Above this the original is not fetched just to report its pixel size. */
const DIMENSION_PROBE_MAX_BYTES = 5 * 1024 * 1024;

/** Original pixel size, read from the file itself since the listing does not carry it. */
watch(() => props.file?.path, (path) => {
  usage.reset();
  pendingCompressionPath.value = null;
  dimensions.value = null;

  if (!path || !isPreviewableImage(path) || (props.file?.size ?? 0) > DIMENSION_PROBE_MAX_BYTES || typeof Image === 'undefined') return;

  const probe = new Image();
  probe.onload = () => {
    if (props.file?.path === path && probe.naturalWidth > 0) {
      dimensions.value = `${probe.naturalWidth} × ${probe.naturalHeight}`;
    }
  };
  probe.src = publicUrl(path);
}, { immediate: true });

function confirmCompression() {
  const path = pendingCompressionPath.value;
  pendingCompressionPath.value = null;
  if (!path || compressing.value) return;

  compressing.value = true;
  router.post(route('files.compress'), { path }, {
    preserveScroll: true,
    preserveState: true,
    onFinish: () => {
      compressing.value = false;
    },
  });
}
</script>
