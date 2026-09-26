<template>
  <Sheet :open="isOpen" @update:open="handleClose">
    <SheetContent side="right" class="w-full sm:max-w-sm overflow-y-auto p-0 border-l border-border bg-background flex flex-col">
      <!-- Header -->
      <div class="flex items-center justify-between border-b border-border px-4 py-3 shrink-0">
        <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-muted-foreground">
          {{ source === 'sharepoint' ? $t('files.ui.sharepoint_properties') : $t('Failo informacija') }}
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
            class="inline-flex min-h-10 w-full items-center justify-center gap-2 bg-brand-fill px-4 text-xs font-bold uppercase tracking-wide text-brand-foreground transition-colors hover:bg-brand-fill/90"
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
            v-if="source === 'local'"
            type="button"
            class="inline-flex min-h-9 items-center justify-center gap-1.5 border border-border bg-secondary/40 px-2.5 text-xs font-semibold text-foreground transition-colors hover:bg-secondary"
            @click="$emit('preview')"
          >
            <Eye class="size-3.5 text-muted-foreground" aria-hidden="true" />
            <span>{{ $t('Peržiūra') }}</span>
          </button>

          <!-- Download (Local) -->
          <a
            v-if="source === 'local' && selectedFile"
            :href="`/uploads/${selectedFile.replace(/^public\//, '')}`"
            target="_blank"
            download
            class="inline-flex min-h-9 items-center justify-center gap-1.5 border border-border bg-secondary/40 px-2.5 text-xs font-semibold text-foreground transition-colors hover:bg-secondary"
          >
            <Download class="size-3.5 text-muted-foreground" aria-hidden="true" />
            <span>{{ $t('Siųsti') }}</span>
          </a>

          <!-- Star / Favorite Toggle (Local) -->
          <button
            v-if="source === 'local'"
            type="button"
            class="inline-flex min-h-9 items-center justify-center gap-1.5 border border-border bg-secondary/40 px-2.5 text-xs font-semibold text-foreground transition-colors hover:bg-secondary"
            @click="$emit('toggleStar')"
          >
            <Star class="size-3.5" :class="{ 'fill-status-attention text-status-attention': isStarred }" aria-hidden="true" />
            <span>{{ isStarred ? $t('Nuimti') : $t('Žymėti') }}</span>
          </button>

          <!-- Copy URL (Local) -->
          <button
            v-if="source === 'local'"
            type="button"
            class="inline-flex min-h-9 items-center justify-center gap-1.5 border border-border bg-secondary/40 px-2.5 text-xs font-semibold text-foreground transition-colors hover:bg-secondary"
            @click="copyUrl"
          >
            <Copy class="size-3.5 text-muted-foreground" aria-hidden="true" />
            <span>{{ $t('Kopijuoti') }}</span>
          </button>

          <!-- Scan Usage (Local) -->
          <button
            v-if="source === 'local'"
            type="button"
            :disabled="scanningUsage"
            class="inline-flex min-h-9 items-center justify-center gap-1.5 border border-border bg-secondary/40 px-2.5 text-xs font-semibold text-foreground transition-colors hover:bg-secondary disabled:opacity-50"
            @click="scanFileUsage"
          >
            <Spinner v-if="scanningUsage" class="size-3.5" />
            <Search v-else class="size-3.5 text-muted-foreground" aria-hidden="true" />
            <span>{{ scanningUsage ? $t('files.ui.searching') : $t('Tikrinti') }}</span>
          </button>

          <!-- Optimize (Local, large images) -->
          <button
            v-if="source === 'local' && showCompress"
            type="button"
            :disabled="compressing"
            class="inline-flex min-h-9 items-center justify-center gap-1.5 border border-border bg-secondary/40 px-2.5 text-xs font-semibold text-foreground transition-colors hover:bg-secondary disabled:opacity-50"
            :title="compressTitle"
            @click="confirmAndCompress"
          >
            <Spinner v-if="compressing" class="size-3.5" />
            <Image v-else class="size-3.5 text-muted-foreground" aria-hidden="true" />
            <span>{{ compressing ? '...' : $t('Optimizuoti') }}</span>
          </button>

          <!-- SharePoint: Open / Copy / Create public permission -->
          <template v-if="source === 'sharepoint'">
            <a
              v-if="publicWebUrl"
              :href="publicWebUrl"
              target="_blank"
              rel="noopener noreferrer"
              class="inline-flex min-h-9 items-center justify-center gap-1.5 border border-border bg-secondary/40 px-2.5 text-xs font-semibold text-foreground transition-colors hover:bg-secondary"
            >
              <ExternalLink class="size-3.5 text-muted-foreground" aria-hidden="true" />
              <span>{{ $t('Atidaryti') }}</span>
            </a>
            <button
              v-if="publicWebUrl"
              type="button"
              class="inline-flex min-h-9 items-center justify-center gap-1.5 border border-border bg-secondary/40 px-2.5 text-xs font-semibold text-foreground transition-colors hover:bg-secondary"
              @click="copySharePointUrl"
            >
              <Copy class="size-3.5 text-muted-foreground" aria-hidden="true" />
              <span>{{ $t('Kopijuoti') }}</span>
            </button>
            <button
              v-else-if="!loadingPublicPermission"
              type="button"
              class="col-span-2 inline-flex min-h-9 items-center justify-center gap-1.5 border border-border bg-secondary/40 px-2.5 text-xs font-semibold text-foreground transition-colors hover:bg-secondary"
              @click="createPublicPermission"
            >
              <Link2 class="size-3.5 text-muted-foreground" aria-hidden="true" />
              <span>{{ $t('Sukurti viešą nuorodą') }}</span>
            </button>
            <div v-if="loadingPublicPermission" class="col-span-2 flex justify-center py-2">
              <Spinner class="size-4" />
            </div>
          </template>

          <!-- Delete Action -->
          <button
            type="button"
            :class="[
              'col-span-2 inline-flex min-h-9 items-center justify-center gap-1.5 border border-border bg-background px-2.5 text-xs font-semibold text-destructive transition-colors hover:bg-destructive/10',
            ]"
            @click="handleDelete"
          >
            <Trash2 class="size-3.5" aria-hidden="true" />
            <span>{{ $t('files.ui.delete') }}</span>
          </button>
        </div>

        <!-- Usage Results Card (Local) -->
        <div v-if="usageData" class="border border-border p-3 text-xs space-y-2 bg-muted/20">
          <div class="flex items-center justify-between">
            <span class="font-semibold text-foreground">{{ $t('Naudojimo patikra') }}</span>
            <span
              :class="[
                'px-1.5 py-0.5 text-[10px] font-bold uppercase',
                usageData.is_safe_to_delete
                  ? 'bg-emerald-500/10 text-emerald-600'
                  : 'bg-rose-500/10 text-rose-600',
              ]"
            >
              {{ usageData.is_safe_to_delete ? $t('Saugu trinti') : $t('Naudojamas') }}
            </span>
          </div>

          <p class="text-muted-foreground text-[11px]">
            {{ usageData.is_safe_to_delete ? $t('files.messages.usage_safe', { count: usageData.total_usages }) : $t('files.messages.usage_found', { count: usageData.total_usages }) }}
          </p>

          <div v-if="usageData.usages && usageData.usages.length > 0" class="space-y-1.5 max-h-40 overflow-y-auto pt-1">
            <div
              v-for="(usage, index) in usageData.usages"
              :key="index"
              class="p-1.5 bg-background border border-border/60 text-[11px]"
            >
              <p class="font-medium text-foreground truncate">
                {{ usage.title }}
              </p>
              <p class="text-muted-foreground text-[10px]">
                {{ getModelDisplayName(usage.model_type) }}
              </p>
            </div>
          </div>
        </div>
      </div>
    </SheetContent>
  </Sheet>
  <ConfirmDialog
    :open="pendingCompressionPath !== null"
    :title="$t('Optimizuoti paveikslėlį?')"
    :description="$t('Paveikslėlis bus perrašytas.')"
    :confirm-label="$t('Optimizuoti paveikslėlį')"
    @update:open="handleCompressionDialogOpen"
    @confirm="confirmCompression"
  />
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import { useFetch } from '@vueuse/core';
import { trans as $t } from 'laravel-vue-i18n';
import {
  Copy,
  Download,
  ExternalLink,
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
import { useToasts } from '@/Composables/useToasts';
import { getFileIcon } from '@/Utils/fileIcons';

interface SharePointFileEntry {
  id?: string;
  name?: string;
  size?: number;
  folder?: unknown;
  lastModifiedDateTime?: string;
  parentReference?: { path?: string };
  [key: string]: unknown;
}

interface FileUsageItem {
  title?: string;
  model_type: string;
  [key: string]: unknown;
}

interface FileUsageResult {
  is_safe_to_delete: boolean;
  total_usages: number;
  usages?: FileUsageItem[];
  [key: string]: unknown;
}

const props = withDefaults(
  defineProps<{
    selectedFile: string | null;
    files: Array<FileEntry | Record<string, unknown>>;
    source?: 'local' | 'sharepoint';
    sharepointFile?: SharePointFileEntry | null;
    selectionMode?: boolean;
    isStarred?: boolean;
  }>(),
  {
    source: 'local',
    sharepointFile: null,
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
const compressing = ref(false);
const pendingCompressionPath = ref<string | null>(null);

const loadingPublicPermission = ref(false);
const publicWebUrl = ref<string | null>(null);

const isOpen = computed(() => {
  if (props.source === 'sharepoint') {
    return !!props.sharepointFile;
  }
  return !!props.selectedFile;
});

const fileName = computed(() => {
  if (props.source === 'sharepoint') {
    return props.sharepointFile?.name ?? 'Unknown file';
  }
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
  const name = props.source === 'sharepoint'
    ? props.sharepointFile?.name
    : props.selectedFile?.split('/').pop();

  if (!name) return '';
  const extension = name.split('.').pop()?.toLowerCase();
  return extension ? extension.toUpperCase() : 'File';
});

const fileSize = computed(() => {
  if (props.source === 'sharepoint') {
    const size = props.sharepointFile?.size;
    if (!size) return '—';
    return formatBytes(size);
  }

  if (!props.selectedFile) return '—';
  const fileInfo = props.files?.find(file => file.path === props.selectedFile);
  return formatBytes(fileInfo?.size);
});

const fileDate = computed(() => {
  if (props.source === 'sharepoint') {
    const dateStr = props.sharepointFile?.lastModifiedDateTime;
    if (!dateStr) return '—';
    return new Date(dateStr).toLocaleDateString('lt-LT');
  }

  if (!props.selectedFile) return '—';
  const fileInfo = props.files?.find((file: FileEntry) => file.path === props.selectedFile);
  if (fileInfo?.modified) {
    const ts = fileInfo.modified < 10000000000 ? fileInfo.modified * 1000 : fileInfo.modified;
    return new Date(ts).toLocaleDateString('lt-LT');
  }
  return '—';
});

const relativePath = computed(() => {
  if (props.source === 'sharepoint') {
    const parentPath = props.sharepointFile?.parentReference?.path;
    if (!parentPath) return '/';
    const parts = parentPath.split('/');
    return parts[parts.length - 1] || '/';
  }

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

watch([() => props.selectedFile, () => props.sharepointFile], () => {
  pendingCompressionPath.value = null;
  usageData.value = null;
  usageError.value = null;
  publicWebUrl.value = null;
  thumbnailFailed.value = false;

  if (props.source === 'sharepoint' && props.sharepointFile?.id && !props.sharepointFile?.folder) {
    fetchPublicLink();
  }
});

async function fetchPublicLink() {
  if (!props.sharepointFile?.id) return;

  loadingPublicPermission.value = true;
  try {
    const { data } = await useFetch(
      route('sharepoint.getDriveItemPublicLink', props.sharepointFile.id),
    ).json();

    if (data.value && Object.keys(data.value).length > 0) {
      publicWebUrl.value = data.value;
    }
    else {
      publicWebUrl.value = null;
    }
  }
  finally {
    loadingPublicPermission.value = false;
  }
}

async function createPublicPermission() {
  if (!props.sharepointFile?.id) {
    toast.error('No file selected');
    return;
  }

  if (props.sharepointFile?.folder) {
    toast.error('Cannot create public link for folders.');
    return;
  }

  loadingPublicPermission.value = true;

  const { data, error } = await useFetch(
    route('sharepoint.createPublicPermission', props.sharepointFile.id),
    {
      headers: {
        'X-CSRF-TOKEN': usePage().props.csrf_token as string,
        'Content-Type': 'application/json',
      },
    },
  ).post().json();

  loadingPublicPermission.value = false;

  if (error.value || !data.value?.success) {
    toast.error(data.value?.error || 'Failed to create public link');
    return;
  }

  publicWebUrl.value = data.value.url;
  toast.success('Public link created successfully');
}

function copyUrl() {
  if (!props.selectedFile) return;
  const url = `${window.location.origin}/uploads/${props.selectedFile.replace(/^public\//, '')}`;
  navigator.clipboard.writeText(url);
  toast.success($t('Nuoroda nukopijuota į iškarpinę'));
}

function copySharePointUrl() {
  if (!publicWebUrl.value) return;
  navigator.clipboard.writeText(publicWebUrl.value);
  toast.success($t('Nuoroda nukopijuota į iškarpinę'));
}

function handleDelete() {
  emit('delete');
}

function scanFileUsage() {
  if (props.source !== 'local') return;
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
        usageData.value = page.props.flash.data;
      }
      if (page.props.flash?.success) {
        toasts.success('Scan completed', { description: page.props.flash.success });
      }
      else if (page.props.flash?.info) {
        toasts.info('Scan completed', { description: page.props.flash.info });
      }
    },
    onError: (errors) => {
      console.error('File usage scan failed:', errors);
      usageError.value = (errors.error as string) || 'Unknown error occurred';
      toasts.error('Failed to scan file usage');
    },
    onFinish: () => {
      scanningUsage.value = false;
    },
  });
}

const eligibleExtensions = ['JPG', 'JPEG', 'PNG'];

const showCompress = computed(() => {
  if (props.source !== 'local') return false;
  if (!props.selectedFile) return false;
  if (!eligibleExtensions.includes(fileExtension.value.toUpperCase())) return false;
  const fileInfo = props.files?.find(f => f.path === props.selectedFile);
  return !!(fileInfo?.size && fileInfo.size > 500 * 1024);
});

const compressTitle = computed(() => {
  return compressing.value ? $t('Optimizuojamas paveikslėlis...') : $t('Optimizuoti paveikslėlį');
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
      toasts.success($t('Paveikslėlis optimizuotas'));
      router.reload({ only: ['files'] });
    },
    onError: (errors) => {
      toasts.error($t('Nepavyko optimizuoti paveikslėlio'), { description: (errors.error as string) || 'Unknown error' });
    },
    onFinish: () => {
      compressing.value = false;
    },
  });
}

function getModelDisplayName(modelType: string): string {
  const modelNames: Record<string, string> = {
    calendar: 'Calendar Events',
    news: 'News Articles',
    duties: 'Duties',
    institutions: 'Institutions',
    types: 'Types',
    forms: 'Forms',
    dutiables: 'Duty Assignments',
    contentParts: 'Content Parts',
    page: 'Pages',
    tenant: 'Tenants',
  };
  return modelNames[modelType] || modelType;
}
</script>
