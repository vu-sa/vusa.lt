<template>
  <div
    ref="dropZoneRef"
    data-slot="fileable-files-panel"
    :class="['relative', isOverDropZone && canUpload && 'outline-1 outline-dashed outline-brand outline-offset-4']"
  >
    <SectionCard
      :title="$t('Failai')"
      :icon="FolderOpen"
      :count="files?.length || undefined"
      :empty="!loading && isEmpty"
    >
      <template v-if="canUpload" #action>
        <Button type="button" variant="outline" size="sm" voice="sentence" @click="openUpload()">
          <Upload class="size-3.5" />
          {{ $t('Įkelti failus') }}
        </Button>
      </template>

      <template #empty>
        <EmptyState
          :title="$t('Failų dar nėra')"
          :description="canUpload ? $t('Nutempk failus čia arba spausk „Įkelti failus“.') : undefined"
        />
      </template>

      <div v-if="loading" class="space-y-2">
        <Skeleton v-for="n in 3" :key="n" class="h-10 w-full" />
      </div>

      <div v-else class="space-y-5">
        <ul v-if="primaryTypes.length" class="divide-y divide-border" data-slot="fileable-primary-files">
          <template v-for="type in primaryTypes" :key="type">
            <FileRow
              v-for="file in filesOfType(type)"
              :key="file.id"
              :file
              :can-delete
              @copy="copyLink"
              @delete="fileToDelete = $event"
            />
            <li v-if="filesOfType(type).length === 0" class="py-1.5">
              <button
                v-if="canUpload"
                type="button"
                :data-missing-type="type"
                :class="[
                  'flex min-h-11 w-full items-center gap-3 border border-dashed border-border px-3 text-left text-sm',
                  'text-muted-foreground transition-colors hover:border-brand hover:text-foreground',
                  'focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring',
                ]"
                @click="openUpload(type)"
              >
                <CircleDashed class="size-4 shrink-0" aria-hidden="true" />
                <span class="flex-1">{{ $t('Neįkelta') }}: {{ $t(fileTypeLabel(type)).toLowerCase() }}</span>
                <span class="font-medium text-foreground">{{ $t('Įkelti') }}</span>
              </button>
              <p v-else class="flex min-h-11 items-center gap-3 px-3 text-sm text-muted-foreground">
                <CircleDashed class="size-4 shrink-0" aria-hidden="true" />
                {{ $t('Neįkelta') }}: {{ $t(fileTypeLabel(type)).toLowerCase() }}
              </p>
            </li>
          </template>
        </ul>

        <ul v-if="otherFiles.length" class="divide-y divide-border" data-slot="fileable-other-files">
          <FileRow
            v-for="file in otherFiles"
            :key="file.id"
            :file
            :can-delete
            @copy="copyLink"
            @delete="fileToDelete = $event"
          />
        </ul>

        <section v-if="typeFiles?.length" class="space-y-1" data-slot="fileable-type-files">
          <h3 class="text-[11px] font-semibold uppercase tracking-wide text-muted-foreground">
            {{ $t('Tipo dokumentai') }}
          </h3>
          <ul class="divide-y divide-border">
            <FileRow v-for="file in typeFiles" :key="file.id" :file :can-delete="false" @copy="copyLink" />
          </ul>
        </section>
      </div>
    </SectionCard>

    <FileableUploadSheet
      v-if="canUpload"
      v-model:open="uploadOpen"
      :fileable
      :primary-types
      :existing-types
      :default-date
      :preset-type
      :initial-files
    />

    <ConfirmDialog
      :open="fileToDelete !== null"
      :title="$t('Ištrinti failą?')"
      :description="$t('Failas bus perkeltas į SharePoint šiukšlinę, o jo vieša nuoroda nustos veikti.')"
      :confirm-label="$t('Ištrinti')"
      destructive
      @update:open="(open) => { if (!open) fileToDelete = null; }"
      @confirm="deleteFile"
    />
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { router, useHttp } from '@inertiajs/vue3';
import { useDropZone } from '@vueuse/core';
import { trans as $t } from 'laravel-vue-i18n';
import { CircleDashed, FolderOpen, Upload } from 'lucide-vue-next';

import FileRow from './FileableFileRow.vue';
import FileableUploadSheet from './FileableUploadSheet.vue';
import type { FileableFileItem } from './types';

import { ConfirmDialog, EmptyState, SectionCard } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { Skeleton } from '@/Components/ui/skeleton';
import { type FileableFileType, fileTypeLabel } from '@/Constants/fileTypes';
import { useToasts } from '@/Composables/useToasts';

const props = withDefaults(defineProps<{
  fileable: { id: string | number; type: string };
  /** Undefined while a deferred prop is still loading. */
  files?: FileableFileItem[];
  /** Read-only reference files from the record's types. */
  typeFiles?: FileableFileItem[];
  canUpload?: boolean;
  canDelete?: boolean;
  /** Always shown, as a slot to fill when missing (a meeting's protocol and report). */
  primaryTypes?: FileableFileType[];
  defaultDate?: string;
}>(), {
  files: undefined,
  typeFiles: undefined,
  primaryTypes: () => [],
  defaultDate: undefined,
});

const toasts = useToasts();
const http = useHttp({});

const uploadOpen = ref(false);
const presetType = ref<FileableFileType | null>(null);
const initialFiles = ref<File[]>([]);
const fileToDelete = ref<FileableFileItem | null>(null);
const dropZoneRef = ref<HTMLElement | null>(null);

const loading = computed(() => props.files === undefined);
const allFiles = computed(() => props.files ?? []);
const isEmpty = computed(() => allFiles.value.length === 0 && props.primaryTypes.length === 0 && !props.typeFiles?.length);
const existingTypes = computed(() => allFiles.value.map(file => file.file_type).filter((type): type is string => !!type));

const filesOfType = (type: string) => allFiles.value.filter(file => file.file_type === type);
const otherFiles = computed(() => allFiles.value.filter(file => !props.primaryTypes.includes(file.file_type as FileableFileType)));

const openUpload = (type: FileableFileType | null = null, files: File[] = []) => {
  presetType.value = type;
  initialFiles.value = files;
  uploadOpen.value = true;
};

const { isOverDropZone } = useDropZone(dropZoneRef, {
  onDrop: (files) => {
    if (props.canUpload && files?.length && !uploadOpen.value) {
      openUpload(null, files);
    }
  },
  multiple: true,
});

const copyLink = (file: FileableFileItem) => {
  http.post(route('fileableFiles.publicLink', file.id), {
    onSuccess: async (data) => {
      const url = (data as { url?: string } | null)?.url;
      try {
        await navigator.clipboard.writeText(url ?? '');
        toasts.success($t('common.link_copied'));
      }
      catch {
        toasts.error($t('common.link_copy_failed'));
      }
    },
    onHttpException: (response) => {
      toasts.error((response.data as { message?: string } | undefined)?.message ?? $t('common.link_copy_failed'));
    },
    onNetworkError: () => toasts.error($t('common.link_copy_failed')),
  });
};

const deleteFile = () => {
  const file = fileToDelete.value;
  fileToDelete.value = null;

  if (file) {
    router.delete(route('fileableFiles.destroy', file.id), { preserveScroll: true });
  }
};

defineExpose({ openUpload });
</script>
