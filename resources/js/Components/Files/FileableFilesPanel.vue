<template>
  <div
    ref="dropZoneRef"
    data-slot="fileable-files-panel"
    :class="['relative', isOverDropZone && canUpload && 'outline-1 outline-dashed outline-brand outline-offset-4']"
  >
    <StagingNote v-if="canUpload" topic="sharepoint" class="mb-3" />
    <OverviewSection variant="home" :title="$t('Failai')" :icon="FolderOpen" :count="files?.length || undefined">
      <template v-if="canUpload" #actions>
        <SectionHeaderAction :icon="Upload" :label="$t('Įkelti failus')" @click="openUpload()" />
      </template>

      <div v-if="loading" class="space-y-2">
        <Skeleton v-for="n in 3" :key="n" class="h-12 w-full" />
      </div>

      <p v-else-if="isEmpty" class="text-sm text-muted-foreground" data-slot="fileable-files-empty">
        {{ $t('Failų dar nėra') }}<template v-if="canUpload">
          — {{ $t('Nutempk failus čia arba spausk „Įkelti failus“.') }}
        </template>
      </p>

      <div v-else class="space-y-6">
        <ul v-if="primaryTypes.length || otherFiles.length" :class="ROW_LIST_CLASS" data-slot="fileable-primary-files">
          <template v-for="type in primaryTypes" :key="type">
            <FileRow
              v-for="file in filesOfType(type)"
              :key="file.id"
              :file
              :can-delete
              @copy="copyLink"
              @delete="fileToDelete = $event" @revoke="linkToRevoke = $event"
            />
            <li v-if="filesOfType(type).length === 0" :class="ROW_PADDING_CLASS">
              <FillActionButton
                v-if="canUpload"
                :data-missing-type="type"
                :label="`${$t('Neįkelta')}: ${$t(fileTypeLabel(type)).toLowerCase()}`"
                :icon="Upload"
                @click="openUpload(type)"
              />
              <p v-else class="flex min-h-9 items-center gap-2 text-sm text-muted-foreground">
                <CircleDashed class="size-4 shrink-0" aria-hidden="true" />
                {{ $t('Neįkelta') }}: {{ $t(fileTypeLabel(type)).toLowerCase() }}
              </p>
            </li>
          </template>
          <FileRow
            v-for="file in otherFiles"
            :key="file.id"
            :file
            :can-delete
            @copy="copyLink"
            @delete="fileToDelete = $event" @revoke="linkToRevoke = $event"
          />
        </ul>

        <section v-if="typeFiles?.length" data-slot="fileable-type-files">
          <h3 class="mb-2 text-[11px] font-bold uppercase tracking-[0.18em] text-muted-foreground">
            {{ $t('Tipo dokumentai') }} · {{ typeFiles.length }}
          </h3>
          <ul :class="ROW_LIST_CLASS">
            <FileRow v-for="file in typeFiles" :key="file.id" :file :can-delete="false" @copy="copyLink" />
          </ul>
        </section>
      </div>

      <p class="mt-1 text-xs text-muted-foreground" data-slot="fileable-files-privacy">
        {{ $t('files.record.privacy_note') }}
        <a
          :href="filesDocsHref"
          target="_blank"
          rel="noopener noreferrer"
          class="underline underline-offset-4 hover:text-foreground"
        >{{ $t('Plačiau') }}</a><template v-if="folderUrl">
          ·
          <a
            :href="safeHref(folderUrl)"
            target="_blank"
            rel="noopener noreferrer"
            class="underline underline-offset-4 hover:text-foreground"
            data-slot="fileable-sharepoint-folder"
          >{{ $t('files.record.open_in_sharepoint') }}</a>
        </template>
      </p>
    </OverviewSection>

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

    <ConfirmDialog
      :open="linkToRevoke !== null"
      :title="$t('files.record.revoke_title')"
      :description="$t('files.record.revoke_description')"
      :confirm-label="$t('Atšaukti nuorodą')"
      destructive
      @update:open="(open) => { if (!open) linkToRevoke = null; }"
      @confirm="revokeLink"
    />
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import { router, useHttp } from '@inertiajs/vue3';
import { useClipboard, useDropZone } from '@vueuse/core';
import { trans as $t } from 'laravel-vue-i18n';
import { CircleDashed, FolderOpen, Upload } from '@lucide/vue';

import { ROW_LIST_CLASS, ROW_PADDING_CLASS } from '@/Components/Collection/rowList';
import { ConfirmDialog, FillActionButton, OverviewSection, SectionHeaderAction } from '@/Components/Patterns';
import StagingNote from '@/Components/StagingNote.vue';
import { Skeleton } from '@/Components/ui/skeleton';
import { type FileableFileType, fileTypeLabel } from '@/Constants/fileTypes';
import { useDocsHref } from '@/Composables/useDocsHref';
import { useToasts } from '@/Composables/useToasts';
import { safeHref } from '@/Utils/safeHref';

import type { FileableFileItem } from './types';
import FileableUploadSheet from './FileableUploadSheet.vue';
import FileRow from './FileableFileRow.vue';

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
  /** The record's folder in SharePoint itself; only set for people who manage the record. */
  folderUrl?: string | null;
}>(), {
  files: undefined,
  typeFiles: undefined,
  folderUrl: null,
  primaryTypes: () => [],
  defaultDate: undefined,
});

const toasts = useToasts();
const http = useHttp({});

const uploadOpen = ref(false);
const presetType = ref<FileableFileType | null>(null);
const initialFiles = ref<File[]>([]);
const fileToDelete = ref<FileableFileItem | null>(null);
const linkToRevoke = ref<FileableFileItem | null>(null);
const filesDocsHref = useDocsHref('/visak/failai');
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

const { copy: copyUrl } = useClipboard({ legacy: true });

const copyLink = (file: FileableFileItem) => {
  http.post(route('fileableFiles.publicLink', file.id), {
    onSuccess: async (data) => {
      const url = (data as { url?: string } | null)?.url;
      try {
        await copyUrl(url ?? '');
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

const revokeLink = () => {
  const file = linkToRevoke.value;
  linkToRevoke.value = null;

  if (file) {
    router.delete(route('fileableFiles.revokePublicLink', file.id), { preserveScroll: true });
  }
};

defineExpose({ openUpload });
</script>
