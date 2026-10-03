<template>
  <SheetForm
    :open
    :title="$t('Įkelti failus')"
    :save-label
    :processing
    :disabled="rows.length === 0"
    :dirty="rows.length > 0"
    @update:open="emit('update:open', $event)"
    @submit="submit"
  >
    <div
      ref="dropZoneRef"
      data-slot="fileable-drop-zone"
      role="button"
      tabindex="0"
      :class="[
        'flex flex-col items-center gap-1.5 border border-dashed px-4 py-6 text-center transition-colors',
        'cursor-pointer focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring',
        isOverDropZone ? 'border-brand bg-brand/5' : 'border-border bg-secondary/30 hover:bg-secondary/60',
      ]"
      @click="fileInputRef?.click()"
      @keydown.enter.prevent="fileInputRef?.click()"
      @keydown.space.prevent="fileInputRef?.click()"
    >
      <Upload class="size-6 text-muted-foreground" aria-hidden="true" />
      <p class="text-sm font-medium text-foreground">
        {{ $t('Nutempk failus čia arba pasirink') }}
      </p>
      <p class="text-xs text-muted-foreground">
        PDF, DOCX, PPTX, XLSX
      </p>
      <input
        ref="fileInputRef"
        type="file"
        multiple
        class="hidden"
        :accept="FILEABLE_FILE_ACCEPT"
        data-testid="fileable-file-input"
        @change="onInputChange"
      >
    </div>

    <ul v-if="rows.length" class="divide-y divide-border border-y border-border" data-slot="fileable-upload-rows">
      <li v-for="(row, index) in rows" :key="row.key" class="space-y-2 py-3">
        <div class="flex items-center gap-2">
          <FileText class="size-4 shrink-0 text-muted-foreground" aria-hidden="true" />
          <Input
            v-model="row.name"
            size="sm"
            class="min-w-0 flex-1"
            :aria-label="$t('Pavadinimas')"
            @update:model-value="row.nameEdited = true"
          />
          <span class="shrink-0 text-xs text-muted-foreground">.{{ row.extension }}</span>
          <Button
            type="button"
            variant="ghost"
            size="icon-sm"
            class="shrink-0 pointer-coarse:size-11"
            :aria-label="$t('Pašalinti')"
            @click="rows.splice(index, 1)"
          >
            <X class="size-4" />
          </Button>
        </div>
        <div class="grid grid-cols-[1fr_auto] gap-2 pl-6">
          <Select :model-value="row.type" @update:model-value="setType(row, String($event))">
            <SelectTrigger size="sm" :aria-label="$t('Tipas')">
              <SelectValue />
            </SelectTrigger>
            <SelectContent>
              <SelectItem v-for="option in typeOptions" :key="option.value" :value="option.value">
                {{ $t(option.label) }}
              </SelectItem>
            </SelectContent>
          </Select>
          <DatePicker v-model="row.date" size="sm" class="w-44" :placeholder="$t('Data')" />
        </div>
        <p v-if="rowError(index)" class="pl-6 text-xs text-destructive">
          {{ rowError(index) }}
        </p>
      </li>
    </ul>
  </SheetForm>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { useDropZone } from '@vueuse/core';
import { trans as $t } from 'laravel-vue-i18n';
import { FileText, Upload, X } from 'lucide-vue-next';

import { SheetForm } from '@/Components/Patterns';
import { Button } from '@/Components/ui/button';
import { DatePicker } from '@/Components/ui/date-picker';
import { Input } from '@/Components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import {
  FILEABLE_FILE_ACCEPT,
  FILEABLE_FILE_TYPES,
  type FileableFileType,
  fileTypeLabel,
  guessFileType,
} from '@/Constants/fileTypes';
import { splitFileNameAndExtension } from '@/Utils/String';

interface UploadRow {
  key: string;
  file: File;
  originalName: string;
  name: string;
  nameEdited: boolean;
  extension: string;
  type: FileableFileType;
  date: Date | undefined;
}

const props = withDefaults(defineProps<{
  open: boolean;
  fileable: { id: string | number; type: string };
  /** Offered first, and filled in order for files whose name gives no hint (meeting: protocol, report). */
  primaryTypes?: FileableFileType[];
  /** Types the record already has, so a guessed primary type skips them. */
  existingTypes?: string[];
  /** YYYY-MM-DD; a meeting's files are dated by the meeting. */
  defaultDate?: string;
  /** Opened from a "Protokolas neįkeltas" slot. */
  presetType?: FileableFileType | null;
  initialFiles?: File[];
}>(), {
  primaryTypes: () => [],
  existingTypes: () => [],
  defaultDate: undefined,
  presetType: null,
  initialFiles: () => [],
});

const emit = defineEmits<{
  (e: 'update:open', value: boolean): void;
  (e: 'uploaded'): void;
}>();

const rows = ref<UploadRow[]>([]);
const processing = ref(false);
const progress = ref<number | null>(null);
const errors = ref<Record<string, string>>({});
const fileInputRef = ref<HTMLInputElement | null>(null);
const dropZoneRef = ref<HTMLElement | null>(null);

const typeOptions = computed(() => [
  ...FILEABLE_FILE_TYPES.filter(type => props.primaryTypes.includes(type.value)),
  ...FILEABLE_FILE_TYPES.filter(type => !props.primaryTypes.includes(type.value)),
]);

const saveLabel = computed(() => {
  if (processing.value && progress.value !== null) {
    return `${$t('Įkeliama')} ${progress.value}%`;
  }

  return rows.value.length > 1 ? `${$t('Įkelti')} (${rows.value.length})` : $t('Įkelti');
});

const parseDate = (value?: string): Date => {
  if (!value) {
    return new Date();
  }
  const [year, month, day] = value.slice(0, 10).split('-').map(Number);
  return new Date(year!, month! - 1, day!);
};

const formatDate = (date: Date): string => {
  const pad = (value: number) => String(value).padStart(2, '0');
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
};

/** A meeting's own paperwork gets "2026-09-12 protokolas"; its folder already names the meeting. */
const suggestedName = (row: UploadRow): string => {
  if (props.primaryTypes.includes(row.type) && props.defaultDate) {
    return `${props.defaultDate.slice(0, 10)} ${$t(fileTypeLabel(row.type)).toLowerCase()}`;
  }

  return row.originalName;
};

const nextType = (fileName: string): FileableFileType => {
  if (props.presetType && !rows.value.some(row => row.type === props.presetType)) {
    return props.presetType;
  }

  const guessed = guessFileType(fileName);
  if (guessed) {
    return guessed;
  }

  const taken = new Set([...props.existingTypes, ...rows.value.map(row => row.type)]);
  return props.primaryTypes.find(type => !taken.has(type)) ?? 'Kita';
};

const addFiles = (files: File[] | FileList | null | undefined) => {
  for (const file of Array.from(files ?? [])) {
    const { name, extension } = splitFileNameAndExtension(file.name);
    const row: UploadRow = {
      key: `${file.name}-${file.size}-${file.lastModified}-${rows.value.length}`,
      file,
      originalName: name,
      name,
      nameEdited: false,
      extension: extension.replace(/^\./, ''),
      type: nextType(file.name),
      date: parseDate(props.defaultDate),
    };
    row.name = suggestedName(row);
    rows.value.push(row);
  }
};

const setType = (row: UploadRow, type: string) => {
  row.type = type as FileableFileType;
  if (!row.nameEdited) {
    row.name = suggestedName(row);
  }
};

const onInputChange = (event: Event) => {
  const input = event.target as HTMLInputElement;
  addFiles(input.files);
  input.value = '';
};

const { isOverDropZone } = useDropZone(dropZoneRef, {
  onDrop: files => addFiles(files),
  multiple: true,
});

watch(() => props.open, (isOpen) => {
  if (!isOpen) {
    return;
  }
  rows.value = [];
  errors.value = {};
  addFiles(props.initialFiles);
}, { immediate: true });

const rowError = (index: number): string | undefined =>
  errors.value[`files.${index}.file`]
  ?? errors.value[`files.${index}.name`]
  ?? errors.value[`files.${index}.type`]
  ?? errors.value[`files.${index}.date`];

const submit = () => {
  if (rows.value.length === 0 || processing.value) {
    return;
  }

  router.post(
    route('fileableFiles.store', { type: props.fileable.type, id: props.fileable.id }),
    {
      files: rows.value.map(row => ({
        file: row.file,
        type: row.type,
        date: formatDate(row.date ?? parseDate(props.defaultDate)),
        name: row.name.trim() || null,
      })),
    },
    {
      forceFormData: true,
      preserveScroll: true,
      onStart: () => {
        processing.value = true;
        errors.value = {};
      },
      onProgress: (event) => {
        progress.value = event?.percentage ?? null;
      },
      onSuccess: (page) => {
        const failed = (page.props.flash as { data?: { failed_file_indexes?: number[] } } | undefined)
          ?.data?.failed_file_indexes;

        if (failed?.length) {
          rows.value = rows.value.filter((_, index) => failed.includes(index));
          return;
        }

        rows.value = [];
        emit('update:open', false);
        emit('uploaded');
      },
      onError: (validationErrors) => {
        errors.value = validationErrors;
      },
      onFinish: () => {
        processing.value = false;
        progress.value = null;
      },
    },
  );
};
</script>
