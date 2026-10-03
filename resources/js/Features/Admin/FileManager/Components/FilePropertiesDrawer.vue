<template>
  <Sheet :open="isOpen" @update:open="handleClose">
    <SheetContent side="right" class="w-full sm:max-w-sm overflow-y-auto p-0 border-l border-border bg-background flex flex-col">
      <!-- Header -->
      <div class="flex items-center justify-between border-b border-border px-4 py-3 shrink-0">
        <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-muted-foreground">
          {{ $t('Failo informacija') }}
        </p>
      </div>

      <div class="flex-1 overflow-y-auto p-4 space-y-4">
        <!-- Media Frame (4:3) -->
        <div class="flex aspect-[4/3] w-full items-center justify-center overflow-hidden border border-border bg-secondary/40">
          <img
            v-if="isImage && !thumbnailFailed"
            :src="thumbnailSrc"
            :alt="fileName"
            class="size-full object-cover"
            @error="thumbnailFailed = true"
          >
          <component :is="typeIcon" v-else class="size-12 text-muted-foreground" aria-hidden="true" />
        </div>

        <!-- File Title & Path -->
        <div>
          <h3 class="break-words text-sm font-bold text-foreground leading-snug">
            {{ fileName }}
          </h3>
          <p class="mt-1 break-words text-xs text-muted-foreground">
            {{ relativePath }}
          </p>
        </div>

        <!-- Metadata Definition List -->
        <dl class="flex flex-col gap-2 text-xs border-y border-border/60 py-3">
          <div class="flex items-center justify-between gap-2">
            <dt class="text-muted-foreground">
              {{ $t('files.ui.type') }}
            </dt>
            <dd class="text-right font-medium text-foreground">
              {{ fileExtension }}
            </dd>
          </div>
          <div class="flex items-center justify-between gap-2">
            <dt class="text-muted-foreground">
              {{ $t('files.ui.size') }}
            </dt>
            <dd class="text-right font-medium text-foreground">
              {{ fileSize }}
            </dd>
          </div>
          <div class="flex items-center justify-between gap-2">
            <dt class="text-muted-foreground">
              {{ $t('files.ui.modified') }}
            </dt>
            <dd class="text-right font-medium text-foreground">
              {{ fileDate }}
            </dd>
          </div>
          <div class="flex items-center justify-between gap-2">
            <dt class="text-muted-foreground">
              {{ $t('files.ui.location') }}
            </dt>
            <dd class="text-right font-medium text-foreground truncate max-w-[180px]">
              {{ relativePath }}
            </dd>
          </div>
        </dl>

        <!-- Selection Mode Primary Action -->
        <div v-if="selectionMode" class="pt-1">
          <button
            type="button"
            class="inline-flex min-h-11 w-full items-center justify-center gap-2 bg-brand-fill px-4 text-xs font-bold uppercase tracking-wide text-brand-foreground transition-colors hover:bg-brand-fill/90"
            @click="$emit('insert')"
          >
            <Link2 class="size-4" aria-hidden="true" />
            <span>{{ $t('Įterpti į puslapį') }}</span>
          </button>
        </div>

        <!-- Action Buttons Grid -->
        <div class="grid grid-cols-2 gap-2 pt-1">
          <!-- Preview (Local) -->
          <button
            type="button"
            class="inline-flex min-h-11 items-center justify-center gap-1.5 border border-border bg-secondary/40 px-2.5 text-xs font-semibold text-foreground transition-colors hover:bg-secondary"
            @click="$emit('preview')"
          >
            <Eye class="size-3.5 text-muted-foreground" aria-hidden="true" />
            <span>{{ $t('Peržiūra') }}</span>
          </button>

          <!-- Download (Local) -->
          <a
            v-if="selectedFile"
            :href="`/uploads/${selectedFile.replace(/^public\//, '')}`"
            target="_blank"
            download
            class="inline-flex min-h-11 items-center justify-center gap-1.5 border border-border bg-secondary/40 px-2.5 text-xs font-semibold text-foreground transition-colors hover:bg-secondary"
          >
            <Download class="size-3.5 text-muted-foreground" aria-hidden="true" />
            <span>{{ $t('Siųsti') }}</span>
          </a>

          <!-- Star / Favorite Toggle (Local) -->
          <button
            type="button"
            class="inline-flex min-h-11 items-center justify-center gap-1.5 border border-border bg-secondary/40 px-2.5 text-xs font-semibold text-foreground transition-colors hover:bg-secondary"
            @click="$emit('toggleStar')"
          >
            <Star class="size-3.5" :class="{ 'fill-status-attention text-status-attention': isStarred }" aria-hidden="true" />
            <span>{{ isStarred ? $t('Nuimti') : $t('Žymėti') }}</span>
          </button>

          <!-- Copy URL (Local) -->
          <button
            type="button"
            class="inline-flex min-h-11 items-center justify-center gap-1.5 border border-border bg-secondary/40 px-2.5 text-xs font-semibold text-foreground transition-colors hover:bg-secondary"
            @click="copyUrl"
          >
            <Copy class="size-3.5 text-muted-foreground" aria-hidden="true" />
            <span>{{ $t('Kopijuoti') }}</span>
          </button>

          <!-- Scan Usage (Local) -->
          <button
            type="button"
            :disabled="scanningUsage"
            class="inline-flex min-h-11 items-center justify-center gap-1.5 border border-border bg-secondary/40 px-2.5 text-xs font-semibold text-foreground transition-colors hover:bg-secondary disabled:opacity-50"
            @click="scanFileUsage"
          >
            <Spinner v-if="scanningUsage" class="size-3.5" />
            <Search v-else class="size-3.5 text-muted-foreground" aria-hidden="true" />
            <span>{{ scanningUsage ? $t('files.ui.searching') : $t('Tikrinti') }}</span>
          </button>

          <!-- Optimize (Local, large images) -->
          <button
            v-if="showCompress"
            type="button"
            :disabled="compressing"
            class="inline-flex min-h-11 items-center justify-center gap-1.5 border border-border bg-secondary/40 px-2.5 text-xs font-semibold text-foreground transition-colors hover:bg-secondary disabled:opacity-50"
            :title="compressTitle"
            @click="confirmAndCompress"
          >
            <Spinner v-if="compressing" class="size-3.5" />
            <Image v-else class="size-3.5 text-muted-foreground" aria-hidden="true" />
            <span>{{ compressing ? '...' : $t('Optimizuoti') }}</span>
          </button>

          <!-- Delete Action -->
          <button
            type="button"
            :class="[
              'col-span-2 inline-flex min-h-11 items-center justify-center gap-1.5 border border-border bg-background px-2.5 text-xs font-semibold text-destructive transition-colors hover:bg-destructive/10',
            ]"
            @click="handleDelete"
          >
            <Trash2 class="size-3.5" aria-hidden="true" />
            <span>{{ $t('files.ui.delete') }}</span>
          </button>
        </div>

        <!-- Usage Results Card (Local) -->
        <div v-if="usageData" class="border border-border p-3 text-xs space-y-2" data-testid="file-usage">
          <div class="flex items-center justify-between gap-2">
            <span class="font-semibold text-foreground">{{ $t('Naudojimo patikra') }}</span>
            <StatusBadge :status="usageStatus" voice="sentence" />
          </div>

          <p class="text-muted-foreground">
            {{ usageData.is_safe_to_delete
              ? $t('files.messages.usage_safe', { count: usageData.scanned_models.length })
              : $t('files.messages.usage_found', { count: usageData.total_usages }) }}
          </p>

          <ul v-if="usageData.usage_details.length > 0" class="max-h-60 divide-y divide-border overflow-y-auto border-t border-border">
            <li v-for="usage in usageData.usage_details" :key="`${usage.model_class}:${usage.id}`">
              <component
                :is="usage.url ? Link : 'div'"
                :href="usage.url ?? undefined"
                :class="[
                  'flex min-h-11 flex-col justify-center py-1.5',
                  usage.url && 'hover:bg-secondary focus-visible:bg-secondary focus-visible:outline-none',
                ]"
              >
                <span class="truncate font-medium text-foreground">{{ usage.title }}</span>
                <span class="text-muted-foreground">
                  {{ $t(`files.usage.models.${usage.model_type}`) }}
                  <template v-if="(usage.matched_parts_count ?? 0) > 1">
                    · {{ $t('files.usage.matched_blocks', { count: usage.matched_parts_count ?? 0 }) }}
                  </template>
                </span>
              </component>
            </li>
          </ul>
        </div>
      </div>
    </SheetContent>
  </Sheet>
  <ConfirmDialog
    :open="pendingCompressionPath !== null"
    :title="$t('files.optimize.dialog_title')"
    :description="$t('files.optimize.dialog_description')"
    :confirm-label="$t('files.optimize.action')"
    @update:open="handleCompressionDialogOpen"
    @confirm="confirmCompression"
  />
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { useClipboard } from '@vueuse/core';
import { trans as $t } from 'laravel-vue-i18n';
import {
  CircleCheck,
  Copy,
  Download,
  Eye,
  Image,
  Link2,
  Search,
  Star,
  Trash2,
} from 'lucide-vue-next';
import { toast } from 'vue-sonner';

import type { FileEntry } from '../types';
import { formatBytes } from '../utils';

import { Sheet, SheetContent } from '@/Components/ui/sheet';
import { Spinner } from '@/Components/ui/spinner';
import ConfirmDialog from '@/Components/Patterns/ConfirmDialog.vue';
import { StatusBadge } from '@/Components/Patterns';
import type { StatusPresentation } from '@/Constants/statuses';
import { useToasts } from '@/Composables/useToasts';
import { getFileIcon } from '@/Utils/fileIcons';

/** Mirrors App\\Services\\FileUsageScanner::scanFileUsage(). */
interface FileUsageDetail {
  model_type: string;
  model_class: string;
  id: number | string;
  title: string;
  url: string | null;
  matched_parts_count?: number;
}

interface FileUsageResult {
  is_safe_to_delete: boolean;
  total_usages: number;
  usage_details: FileUsageDetail[];
  scanned_models: string[];
}

const props = withDefaults(
  defineProps<{
    selectedFile: string | null;
    files: Array<FileEntry | Record<string, unknown>>;
    selectionMode?: boolean;
    isStarred?: boolean;
  }>(),
  {
    selectionMode: false,
    isStarred: false,
  },
);

const emit = defineEmits<{
  (e: 'close'): void;
  (e: 'delete'): void;
  (e: 'preview'): void;
  (e: 'toggleStar'): void;
  (e: 'insert'): void;
}>();

const toasts = useToasts();

const scanningUsage = ref(false);
const usageData = ref<FileUsageResult | null>(null);
const usageError = ref<string | null>(null);

const usageStatus = computed<StatusPresentation>(() => usageData.value?.is_safe_to_delete
  ? { label: 'Saugu trinti', role: 'success', icon: CircleCheck }
  : { label: 'Naudojamas', role: 'attention', icon: Link2 });
const compressing = ref(false);
const pendingCompressionPath = ref<string | null>(null);

const isOpen = computed(() => !!props.selectedFile);

const fileName = computed(() => {
  if (!props.selectedFile) return '';
  return props.selectedFile.split('/').pop() || 'Unknown file';
});

const typeIcon = computed(() => getFileIcon(fileName.value));

const isImage = computed(() => /\.(jpg|jpeg|png|webp|gif|svg|avif)$/i.test(fileName.value));

const thumbnailFailed = ref(false);

const thumbnailSrc = computed(() => {
  if (thumbnailFailed.value) {
    return `/uploads/${props.selectedFile?.replace(/^public\//, '') || ''}`;
  }
  return route('api.v1.admin.files.thumbnail', { path: props.selectedFile, w: 640 });
});

const fileExtension = computed(() => {
  const name = props.selectedFile?.split('/').pop();

  if (!name) return '';
  const extension = name.split('.').pop()?.toLowerCase();
  return extension ? extension.toUpperCase() : 'File';
});

const fileSize = computed(() => {
  if (!props.selectedFile) return '—';
  const fileInfo = props.files?.find(file => file.path === props.selectedFile);
  return formatBytes(fileInfo?.size);
});

const fileDate = computed(() => {
  if (!props.selectedFile) return '—';
  const fileInfo = props.files?.find((file: FileEntry) => file.path === props.selectedFile);
  if (fileInfo?.modified) {
    const ts = fileInfo.modified < 10000000000 ? fileInfo.modified * 1000 : fileInfo.modified;
    return new Date(ts).toLocaleDateString('lt-LT');
  }
  return '—';
});

const relativePath = computed(() => {
  if (!props.selectedFile) return '/';
  const pathWithoutPublicFiles = props.selectedFile.replace(/^public\/files\/?/, '');
  const directory = pathWithoutPublicFiles.substring(0, pathWithoutPublicFiles.lastIndexOf('/'));
  return directory || '/';
});

function handleClose(open: boolean) {
  if (!open) {
    emit('close');
  }
}

watch(() => props.selectedFile, () => {
  pendingCompressionPath.value = null;
  usageData.value = null;
  usageError.value = null;
  thumbnailFailed.value = false;
});

const { copy: copyToClipboard } = useClipboard({ legacy: true });

function copyUrl() {
  if (!props.selectedFile) return;
  const url = `${window.location.origin}/uploads/${props.selectedFile.replace(/^public\//, '')}`;
  void copyToClipboard(url).then(() => {
    toast.success($t('Nuoroda nukopijuota į iškarpinę'));
  });
}

function handleDelete() {
  emit('delete');
}

function scanFileUsage() {
  if (!props.selectedFile || scanningUsage.value) return;

  scanningUsage.value = true;
  usageError.value = null;

  router.post(route('files.scanUsage'), {
    path: props.selectedFile,
  }, {
    preserveState: true,
    preserveScroll: true,
    onSuccess: (page) => {
      if (page.props.flash?.data) {
        usageData.value = page.props.flash.data as FileUsageResult;
      }
      if (page.props.flash?.success) {
        toasts.success($t('files.usage.scan_done'), { description: page.props.flash.success });
      }
      else if (page.props.flash?.info) {
        toasts.info($t('files.usage.scan_done'), { description: page.props.flash.info });
      }
    },
    onError: (errors) => {
      usageError.value = (errors.error as string) || $t('files.usage.scan_failed');
      toasts.error($t('files.usage.scan_failed'), { description: errors.error as string | undefined });
    },
    onFinish: () => {
      scanningUsage.value = false;
    },
  });
}

const eligibleExtensions = ['JPG', 'JPEG', 'PNG'];

const showCompress = computed(() => {
  if (!props.selectedFile) return false;
  if (!eligibleExtensions.includes(fileExtension.value.toUpperCase())) return false;
  const fileInfo = props.files?.find(f => f.path === props.selectedFile);
  return !!(fileInfo?.size && fileInfo.size > 500 * 1024);
});

const compressTitle = computed(() => {
  return compressing.value ? $t('files.optimize.progress') : $t('files.optimize.action');
});

function confirmAndCompress() {
  if (!props.selectedFile || compressing.value) return;
  pendingCompressionPath.value = props.selectedFile;
}

function handleCompressionDialogOpen(open: boolean) {
  if (!open) pendingCompressionPath.value = null;
}

function confirmCompression() {
  const path = pendingCompressionPath.value;
  pendingCompressionPath.value = null;
  if (!path || compressing.value) return;
  compressImage(path);
}

function compressImage(path: string) {
  compressing.value = true;
  router.post(route('files.compress'), { path }, {
    preserveScroll: true,
    preserveState: true,
    onSuccess: () => {
      toasts.success($t('files.optimize.success'));
      router.reload({ only: ['files'] });
    },
    onError: (errors) => {
      toasts.error($t('files.optimize.error'), { description: (errors.error as string) || 'Unknown error' });
    },
    onFinish: () => {
      compressing.value = false;
    },
  });
}
</script>
