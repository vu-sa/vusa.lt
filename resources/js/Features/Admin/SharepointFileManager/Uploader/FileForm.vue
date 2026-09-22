<template>
  <form @submit.prevent="handleValidateClick">
    <div class="space-y-4">
      <FormFieldWrapper id="typeValue" :label="$t('forms.fields.type')" required :error="errors.typeValue">
        <Select v-model="typeValueString" :disabled="!!model.uploadValue">
          <SelectTrigger>
            <SelectValue placeholder="Pasirink failo tipą..." />
          </SelectTrigger>
          <SelectContent>
            <SelectItem v-for="opt in sharepointFileTypeOptions" :key="opt.value" :value="opt.value">
              {{ opt.label }}
            </SelectItem>
          </SelectContent>
        </Select>
      </FormFieldWrapper>

      <FormFieldWrapper id="datetimeValue" :label="$t('Dokumento data')" required :error="errors.datetimeValue">
        <DatePicker v-model="dateValue" placeholder="2022-12-01" />
      </FormFieldWrapper>

      <FormFieldWrapper id="descriptionValue" :label="$t('forms.fields.description')">
        <Textarea v-model="model.description0Value" placeholder="Šis dokumentas yra skirtas..." />
      </FormFieldWrapper>

      <FormFieldWrapper v-if="model.typeValue" id="uploadValue" label="Įkelti failą" required :error="errors.uploadValue">
        <div
          class="relative flex flex-col items-center gap-2 border-2 border-dashed border-border bg-muted/30 p-6 text-center transition-colors hover:bg-muted/50"
          role="button"
          tabindex="0"
          @click="fileInputRef?.click()"
          @keydown.enter="fileInputRef?.click()"
          @dragover.prevent
          @drop.prevent="handleDrop"
        >
          <input
            id="sharepoint-file-input"
            ref="fileInputRef"
            type="file"
            class="absolute inset-0 h-full w-full cursor-pointer opacity-0"
            accept=".pdf,.docx,.pptx"
            @change="handleFileSelect"
          >
          <Archive class="size-10 text-muted-foreground" />
          <p class="font-bold text-foreground">
            Paspausk arba nutempk failą čia
          </p>
          <p class="text-xs text-muted-foreground">
            Pateikite tik galutinį dokumentą, kuris bus patvirtintas
          </p>
          <p v-if="model.uploadValue" class="mt-2 text-sm font-medium text-status-success">
            {{ model.uploadValue.name }}
          </p>
        </div>
      </FormFieldWrapper>

      <FormFieldWrapper v-if="model.typeValue" id="tempNameValue" label="Sugeneruotas failo pavadinimas">
        <div class="flex gap-1">
          <Input
            v-model="model.tempNameValue"
            class="flex-1"
            placeholder=""
            :disabled="fileNameEditDisabled"
          />
          <Input
            class="w-[15%]"
            disabled
            :model-value="fileExtension"
            placeholder=""
          />
        </div>
      </FormFieldWrapper>

      <Button :disabled="!model.uploadValue || loading" type="submit">
        <FilePlus class="mr-2 size-4" />
        {{ $t('Įkelti failą') }}
      </Button>
    </div>
  </form>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';
import { Archive, FilePlus } from 'lucide-vue-next';
import { trans as $t } from 'laravel-vue-i18n';

import { generateNameForFile } from './generateNameForFile';

import FormFieldWrapper from '@/Components/AdminForms/FormFieldWrapper.vue';
import { Button } from '@/Components/ui/button';
import { DatePicker } from '@/Components/ui/date-picker';
import { Input } from '@/Components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Textarea } from '@/Components/ui/textarea';
import { useToasts } from '@/Composables/useToasts';
import { modelTypes } from '@/Types/formOptions';
import { splitFileNameAndExtension } from '@/Utils/String';

export interface SharepointFileSubmitPayload {
  datetimeValue: number | null;
  description0Value: string;
  nameValue: string | null;
  tempNameValue: string | null;
  typeValue: string | null;
  uploadValue: File | null;
}

const emit = defineEmits<{
  (e: 'submit', form: SharepointFileSubmitPayload): void;
  (e: 'close'): void;
}>();

const props = defineProps<{
  fileable?: FileableFormData | null;
  loading: boolean;
}>();

const fileNameEditDisabled = ref(true);
const { error } = useToasts();

const originalFileName = ref('');
const fileExtension = ref<string | undefined>('');
const fileInputRef = ref<HTMLInputElement | null>(null);

const errors = ref<Record<string, string>>({});

const model = ref<{
  datetimeValue: Date | null;
  description0Value: string;
  nameValue: string | null;
  tempNameValue: string | null;
  typeValue: string | null;
  uploadValue: File | null;
}>({
  datetimeValue: null,
  description0Value: '',
  nameValue: null,
  tempNameValue: null,
  typeValue: null,
  uploadValue: null,
});

// Bridge for Select (string) <-> model.typeValue (string | null)
const typeValueString = ref('');
watch(typeValueString, (val) => {
  model.value.typeValue = val || null;
});

// Bridge for DatePicker (Date) <-> model.datetimeValue
const dateValue = ref<Date | undefined>(undefined);
watch(dateValue, (val) => {
  model.value.datetimeValue = val ?? null;
});

const sharepointFileTypeOptions = modelTypes.sharepointFile.map(type => ({
  label: type,
  value: type,
}));

const validateFile = (file: File): boolean => {
  if (!file.type) return false;

  if (model.value.typeValue === 'Pristatymai') {
    if (
      ![
        'application/pdf',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
      ].includes(file.type)
    ) {
      error('Pristatymas turi būti PDF arba PPTX formatu.');
      return false;
    }
    return true;
  }

  if (
    ![
      'application/pdf',
      'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ].includes(file.type)
  ) {
    error('Failas turi būti PDF arba DOCX formatu.');
    return false;
  }
  return true;
};

const processFile = (file: File) => {
  if (!validateFile(file)) return;

  model.value.uploadValue = file;

  const { name, extension } = splitFileNameAndExtension(file.name);
  fileExtension.value = extension;
  originalFileName.value = name;
  model.value.tempNameValue = setUploadFileName();
};

const handleFileSelect = (e: Event) => {
  const input = e.target as HTMLInputElement;
  const file = input.files?.[0];
  if (file) processFile(file);
};

const handleDrop = (e: DragEvent) => {
  const file = e.dataTransfer?.files?.[0];
  if (file) processFile(file);
};

const setUploadFileName = () => {
  fileNameEditDisabled.value = true;

  if (originalFileName.value === '' || model.value.typeValue === null) {
    return null;
  }

  const { fileName, isFileNameEditDisabled } = generateNameForFile(
    {
      dateValue: model.value.datetimeValue ? model.value.datetimeValue.getTime() : null,
      nameValue: originalFileName.value,
      typeValue: model.value.typeValue,
    },
    props.fileable,
  );

  fileNameEditDisabled.value = isFileNameEditDisabled;
  return fileName;
};

const validate = (): boolean => {
  errors.value = {};

  if (!model.value.typeValue) {
    errors.value.typeValue = 'Pasirinkite tipą';
  }
  if (!model.value.datetimeValue) {
    errors.value.datetimeValue = 'Pasirinkite dokumento datą';
  }
  if (!model.value.uploadValue) {
    errors.value.uploadValue = 'Įkelkite failą';
  }

  return Object.keys(errors.value).length === 0;
};

const handleValidateClick = () => {
  if (!validate()) return;

  model.value = {
    ...model.value,
    nameValue: model.value.tempNameValue + fileExtension.value,
  };

  // Convert Date back to timestamp for compatibility with the parent form
  const submitData = {
    ...model.value,
    datetimeValue: model.value.datetimeValue ? model.value.datetimeValue.getTime() : null,
  };

  emit('submit', submitData);
};
</script>
