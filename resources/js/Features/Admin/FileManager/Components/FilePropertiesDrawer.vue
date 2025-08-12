<template>
  <Sheet :open="!!selectedFile" @update:open="handleClose">
    <SheetContent side="bottom" class="max-h-[80vh] overflow-y-auto p-0">
      <div class="p-4 pb-6">
        <SheetHeader class="pb-4">
          <SheetTitle class="text-base font-medium">
            {{ fileName }}
          </SheetTitle>
          <SheetDescription class="text-sm text-muted-foreground">
            File properties and actions
          </SheetDescription>
        </SheetHeader>

        <div class="flex flex-col lg:flex-row gap-4">
          <!-- File Icon -->
          <div class="flex-shrink-0 flex justify-center lg:justify-start">
            <div class="p-3 rounded-lg bg-muted/50 inline-flex">
              <span class="text-vusa-red">
                <!-- PDF files -->
                <IFluentDocumentPdf24Regular v-if="fileExtension.toLowerCase() === 'pdf'" class="h-12 w-12" />
                <!-- Document files -->
                <IFluentDocumentText24Regular
                  v-else-if="['doc', 'docx', 'odt', 'txt', 'rtf'].includes(fileExtension.toLowerCase())"
                  class="h-12 w-12" />
                <!-- Spreadsheet files including CSV -->
                <IFluentDocumentTable24Regular
                  v-else-if="['xls', 'xlsx', 'csv', 'ods'].includes(fileExtension.toLowerCase())" class="h-12 w-12" />
                <!-- Video files -->
                <IFluentVideo24Regular
                  v-else-if="['mp4', 'avi', 'mkv', 'mov', 'webm', 'wmv', 'flv', 'm4v'].includes(fileExtension.toLowerCase())"
                  class="h-12 w-12" />
                <!-- Audio files -->
                <IFluentMusicNote24Regular
                  v-else-if="['mp3', 'wav', 'flac', 'aac', 'ogg', 'm4a', 'wma'].includes(fileExtension.toLowerCase())"
                  class="h-12 w-12" />
                <!-- Archive files -->
                <IFluentFolderZip24Regular
                  v-else-if="['zip', 'rar', '7z', 'tar', 'gz', 'bz2', 'xz'].includes(fileExtension.toLowerCase())"
                  class="h-12 w-12" />
                <!-- Code files -->
                <IFluentCode24Regular
                  v-else-if="['js', 'ts', 'vue', 'html', 'css', 'php', 'py', 'java', 'cpp', 'c', 'h', 'json', 'xml', 'yml', 'yaml'].includes(fileExtension.toLowerCase())"
                  class="h-12 w-12" />
                <!-- Image files -->
                <IFluentImage24Regular
                  v-else-if="['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp', 'tiff', 'ico'].includes(fileExtension.toLowerCase())"
                  class="h-12 w-12" />
                <!-- Default fallback for any other file type -->
                <IFluentDocument24Regular v-else class="h-12 w-12" />
              </span>
            </div>
          </div>

          <div class="flex-1 min-w-0">
            <!-- File info grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
              <div class="flex items-center gap-3 p-3 rounded-lg bg-muted/30">
                <IFluentDocument24Regular class="h-5 w-5 text-muted-foreground flex-shrink-0" />
                <div class="min-w-0">
                  <p class="text-xs text-muted-foreground mb-1">
                    Type
                  </p>
                  <p class="text-sm font-medium truncate">
                    {{ fileExtension }}
                  </p>
                </div>
              </div>

              <div class="flex items-center gap-3 p-3 rounded-lg bg-muted/30">
                <IFluentStorage24Regular class="h-5 w-5 text-muted-foreground flex-shrink-0" />
                <div class="min-w-0">
                  <p class="text-xs text-muted-foreground mb-1">
                    Size
                  </p>
                  <p class="text-sm font-medium">
                    {{ fileSize }}
                  </p>
                </div>
              </div>

              <div class="flex items-center gap-3 p-3 rounded-lg bg-muted/30">
                <IFluentCalendar24Regular class="h-5 w-5 text-muted-foreground flex-shrink-0" />
                <div class="min-w-0">
                  <p class="text-xs text-muted-foreground mb-1">
                    Modified
                  </p>
                  <p class="text-sm font-medium">
                    {{ fileDate }}
                  </p>
                </div>
              </div>

              <div class="flex items-center gap-3 p-3 rounded-lg bg-muted/30">
                <IFluentFolder24Regular class="h-5 w-5 text-muted-foreground flex-shrink-0" />
                <div class="min-w-0">
                  <p class="text-xs text-muted-foreground mb-1">
                    Location
                  </p>
                  <p class="text-sm font-medium truncate">
                    {{ relativePath }}
                  </p>
                </div>
              </div>
            </div>

            <!-- Action buttons -->
            <div class="flex flex-wrap gap-2 mb-6">
              <Button size="sm" class="flex-1 sm:flex-none" @click="$emit('preview')">
                <IFluentOpen24Regular class="h-4 w-4 mr-2" />
                Preview
              </Button>
              <Button :loading="scanningUsage" size="sm" class="flex-1 sm:flex-none" variant="outline"
                @click="scanFileUsage">
                <IFluentSearch24Regular class="h-4 w-4 mr-2" />
                {{ scanningUsage ? 'Scanning...' : 'Scan Usage' }}
              </Button>
              <Button v-if="showCompress" :loading="compressing" size="sm" variant="secondary"
                class="flex-1 sm:flex-none" :title="compressTitle" @click="confirmAndCompress">
                <IFluentImage24Regular class="h-4 w-4 mr-2" />
                {{ compressing ? 'Optimizing...' : 'Optimize' }}
              </Button>
              <Button :disabled="!usageData || (!usageData.is_safe_to_delete)" variant="destructive" size="sm"
                class="flex-1 sm:flex-none"
                :title="!usageData
                  ? 'Scan usage first before deleting'
                  : (!usageData.is_safe_to_delete
                    ? 'File in use – cannot delete'
                    : 'Delete file')"
                @click="handleDelete">
                <IFluentDelete24Filled class="h-4 w-4 mr-2" />
                {{ !usageData ? 'Delete (Scan First)' : 'Delete' }}
              </Button>
            </div>
            <div v-if="showCompress"
              class="mb-6 text-xs bg-amber-50 border border-amber-200 rounded p-3 text-amber-800 flex items-start gap-2">
              <IFluentInformation16Regular class="h-4 w-4 mt-0.5 shrink-0" />
              <p>
                This image is large ({{ fileSize }}). You can optimize it to reduce size. The file will be
                <strong>overwritten</strong>.
              </p>
            </div>

            <!-- Usage scan results -->
            <div v-if="usageData" class="border rounded-lg p-4 bg-muted/20">
              <div class="flex items-center gap-3 mb-4">
                <div class="flex items-center gap-2">
                  <IFluentShieldTask24Regular class="h-5 w-5"
                    :class="usageData.is_safe_to_delete ? 'text-green-600' : 'text-amber-600'" />
                  <h3 class="font-medium text-sm">
                    Usage Analysis
                  </h3>
                </div>
                <Badge :variant="usageData.is_safe_to_delete ? 'secondary' : 'destructive'">
                  {{ usageData.is_safe_to_delete ? 'Safe to Delete' : 'In Use' }}
                </Badge>
              </div>

              <div class="space-y-3">
                <div class="flex items-center text-sm gap-2">
                  <span class="text-muted-foreground shrink-0">Total usages found:</span>
                  <span class="font-medium break-all">{{ usageData.total_usages }}</span>
                </div>

                <div class="flex items-center text-sm gap-2">
                  <span class="text-muted-foreground shrink-0">Last scanned:</span>
                  <span class="font-medium break-all">{{ formatScanTime(usageData.scanned_at) }}</span>
                </div>

                <!-- Usage details -->
                <div v-if="usageData.usage_details.length > 0" class="mt-4">
                  <h4 class="text-sm font-medium mb-2">
                    Found in:
                  </h4>
                  <div class="space-y-2 max-h-40 overflow-y-auto">
                    <div v-for="usage in usageData.usage_details" :key="`${usage.model_type}-${usage.id}`"
                      class="group flex items-center justify-between p-2 bg-muted/50 rounded text-xs transition hover:bg-muted cursor-pointer">
                      <div class="flex-1 min-w-0">
                        <component :is="usage.edit_url ? 'a' : 'div'" :href="usage.edit_url || undefined"
                          target="_blank" rel="noopener noreferrer" class="block">
                          <p class="font-medium truncate group-hover:underline">
                            {{ usage.title }}
                          </p>
                        </component>
                        <p class="text-muted-foreground">
                          {{ getModelDisplayName(usage.model_type) }}
                        </p>
                      </div>
                      <div class="flex items-center gap-1">
                        <a
                          :href="usage.edit_url || undefined"
                          target="_blank"
                          rel="noopener noreferrer"
                          aria-label="Edit item"
                        >
                          <Button v-if="usage.edit_url" size="sm" variant="ghost" as-child class="h-6 w-6 p-0"
                            :title="'Edit ' + usage.title">
                            <IFluentEdit16Regular class="h-3 w-3" />
                          </Button>
                        </a>
                        <Button v-if="usage.url && !usage.edit_url" size="sm" variant="ghost" as-child
                          class="h-6 w-6 p-0" :title="'View ' + usage.title">
                          <a :href="usage.url" target="_blank" rel="noopener noreferrer" aria-label="Open item">
                            <IFluentOpen16Regular class="h-3 w-3" />
                          </a>
                        </Button>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Warning message for files in use -->
                <div v-if="!usageData.is_safe_to_delete"
                  class="mt-4 p-3 bg-amber-50 border border-amber-200 rounded text-xs text-amber-800">
                  <p class="font-medium">
                    ⚠️ Warning
                  </p>
                  <p class="mt-1">
                    This file is currently in use. Deleting it may break content in the listed locations above.
                  </p>
                </div>
              </div>
            </div>

            <!-- Usage scan error -->
            <div v-if="usageError" class="border border-red-200 rounded-lg p-4 bg-red-50">
              <div class="flex items-center gap-2 mb-2">
                <IFluentErrorCircle24Regular class="h-5 w-5 text-red-600" />
                <h3 class="font-medium text-sm text-red-800">
                  Scan Error
                </h3>
              </div>
              <p class="text-xs text-red-700">
                {{ usageError }}
              </p>
            </div>
          </div>
        </div>
      </div>
    </SheetContent>
  </Sheet>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';

import SmartLink from '@/Components/Public/SmartLink.vue';
import { Button } from '@/Components/ui/button';
import { Badge } from '@/Components/ui/badge';
import {
  Sheet,
  SheetContent,
  SheetHeader,
  SheetTitle,
  SheetDescription
} from '@/Components/ui/sheet';
import { useToasts } from '@/Composables/useToasts';
// Icons used inside script-added template changes
import IFluentEdit16Regular from '~icons/fluent/edit-16-regular';
import IFluentInformation16Regular from '~icons/fluent/info-16-regular';

interface FileEntry { path: string; size?: number; modified?: number; }
interface UsageDetail {
  id: number | string;
  model_type: string;
  title: string;
  edit_url?: string; // use undefined instead of null
  url?: string;
}
interface UsageData {
  is_safe_to_delete: boolean;
  total_usages: number;
  usage_details: UsageDetail[];
  scanned_at: string;
}

const props = defineProps<{
  selectedFile: string | null;
  files: FileEntry[];
}>();

const emit = defineEmits<{
  'preview': [];
  'delete': [];
  'close': [];
}>();

// File usage scanning state
const scanningUsage = ref(false);
const usageData = ref<UsageData | null>(null);
const usageError = ref<string | null>(null);
const toasts = useToasts();
const compressing = ref(false);

const fileName = computed(() => {
  if (!props.selectedFile) return '';
  return props.selectedFile.split('/').pop() || 'Unknown file';
});

const fileExtension = computed(() => {
  if (!props.selectedFile) return '';
  const fn = props.selectedFile.split('/').pop() || '';
  const extension = fn.split('.').pop()?.toLowerCase();
  return extension ? extension.toUpperCase() : 'File';
});

const fileSize = computed(() => {
  if (!props.selectedFile) return 'Unknown';
  const fileInfo = props.files.find((file) => file.path === props.selectedFile);
  if (fileInfo?.size) {
    const bytes = fileInfo.size;
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes: string[] = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return `${parseFloat((bytes / Math.pow(k, i)).toFixed(1))} ${sizes[i]}`;
  }
  return 'Unknown';
});

const fileDate = computed(() => {
  if (!props.selectedFile) return 'Unknown';
  const fileInfo = props.files.find((file: FileEntry) => file.path === props.selectedFile);
  if (fileInfo?.modified) {
    return new Date(fileInfo.modified * 1000).toLocaleDateString('lt-LT');
  }
  return 'Unknown';
});

const relativePath = computed(() => {
  if (!props.selectedFile) return '/';
  // Remove public/files prefix and filename to show just the directory
  const pathWithoutPublicFiles = props.selectedFile.replace('public/files/', '');
  const directory = pathWithoutPublicFiles.substring(0, pathWithoutPublicFiles.lastIndexOf('/'));
  return directory || '/';
});

// Handle close with proper emit
function handleClose(open: boolean) {
  if (!open) {
    emit('close');
  }
}

// Clear usage data when file changes
watch(() => props.selectedFile, () => {
  usageData.value = null;
  usageError.value = null;
});

// Handle delete confirmation
function handleDelete() {
  if (!props.selectedFile) return;
  
  // Simply emit delete to let parent handle the confirmation
  emit('delete');
  
  // Close the drawer since deletion will be handled by parent
  emit('close');
}

// File usage scanning
function scanFileUsage() {
  if (!props.selectedFile || scanningUsage.value) return;

  scanningUsage.value = true;
  usageError.value = null;

  router.post(route('files.scanUsage'), {
    path: props.selectedFile
  }, {
    preserveState: true,
    preserveScroll: true,
    onSuccess: (page) => {
      // Check if usage data was returned in flash.data
      if (page.props.flash?.data) {
        usageData.value = page.props.flash.data;
      }

      // Handle flash messages
      if (page.props.flash?.success) {
        toasts.success('Scan completed', {
          description: page.props.flash.success
        });
      } else if (page.props.flash?.info) {
        toasts.info('Scan completed', {
          description: page.props.flash.info
        });
      }
    },
    onError: (errors) => {
      console.error('File usage scan failed:', errors);
      usageError.value = errors.error || 'Unknown error occurred';
      toasts.error('Failed to scan file usage', {
        description: errors.error || 'An unknown error occurred'
      });
    },
    onFinish: () => {
      scanningUsage.value = false;
    }
  });
}

// Image compression state
const eligibleExtensions = ['JPG', 'JPEG', 'PNG'];

const showCompress = computed(() => {
  if (!props.selectedFile) return false;
  if (!eligibleExtensions.includes(fileExtension.value.toUpperCase())) return false;
  const fileInfo = props.files.find((f) => f.path === props.selectedFile);
  return !!(fileInfo?.size && fileInfo.size > 500 * 1024);
});

const compressTitle = computed(() => {
  return compressing.value ? 'Optimizing image...' : 'Optimize image';
});

function confirmAndCompress() {
  if (!props.selectedFile || compressing.value) return;
  const confirmText = 'Optimize this image? It will be overwritten.';
  if (!window.confirm(confirmText)) return;
  compressImage();
}

function compressImage() {
  if (!props.selectedFile) return;
  compressing.value = true;
  router.post(route('files.compress'), { path: props.selectedFile }, {
    preserveScroll: true,
    preserveState: true,
    onSuccess: (page) => {
      toasts.success('Image optimized');
      // Ask parent to refresh listing (parent likely triggers getFiles), otherwise simple location reload
      router.reload({ only: ['files'] });
    },
    onError: (errors) => {
      toasts.error('Failed to optimize image', { description: errors.error || 'Unknown error' });
    },
    onFinish: () => {
      compressing.value = false;
    }
  });
}

// Format scan timestamp
function formatScanTime(timestamp: string): string {
  try {
    return new Date(timestamp).toLocaleString('lt-LT', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit'
    });
  } catch {
    return 'Unknown';
  }
}

// Get display name for model types
function getModelDisplayName(modelType: string): string {
  const modelNames: Record<string, string> = {
    calendar: 'Calendar Events',
    news: 'News Articles',
    duties: 'Duties',
    institutions: 'Institutions',
    trainings: 'Trainings',
    types: 'Types',
    forms: 'Forms',
    changelogItems: 'Changelog Items',
    dutiables: 'Duty Assignments',
    contentParts: 'Content Parts',
    page: 'Pages',
    tenant: 'Tenants'
  };

  return modelNames[modelType] || modelType;
}
</script>
