<template>
  <CardModal :show :title="modalTitle" @close="$emit('close')">
    <Stepper v-if="!props.fileable" v-model="stepperStep" class="my-2 py-2">
      <StepperItem :step="1">
        <StepperTrigger>
          <StepperIndicator>
            <IFluentDocumentTableSearch24Regular class="h-4 w-4" />
          </StepperIndicator>
        </StepperTrigger>
        <StepperTitle>{{ $t('Į ką kelsi failą?') }}</StepperTitle>
        <StepperSeparator />
      </StepperItem>
      <StepperItem :step="2">
        <StepperTrigger>
          <StepperIndicator>2</StepperIndicator>
        </StepperTrigger>
        <StepperTitle>{{ $t('Failo įkėlimas') }}</StepperTitle>
      </StepperItem>
    </Stepper>
    <FadeTransition>
      <FileableForm v-if="current === 1" :show-alert @close:alert="showAlert = false"
        @submit="handleFileableSubmit" />
      <div v-else-if="current === 2">
        <FileForm :fileable="fileForm.fileable" :loading @submit="handleFileSubmit" />
      </div>
    </FadeTransition>
    <FadeTransition>
      <ModalHelperButton v-if="!showAlert && current === 1" @click="showAlert = true" />
    </FadeTransition>
  </CardModal>
</template>

<script setup lang="ts">
import { computed, inject, ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { useStorage } from '@vueuse/core';
import { trans as $t } from 'laravel-vue-i18n';

import FileForm from './FileForm.vue';

import { Stepper, StepperIndicator, StepperItem, StepperSeparator, StepperTitle, StepperTrigger } from '@/Components/ui/stepper';
import CardModal from '@/Components/Modals/CardModal.vue';
import FadeTransition from '@/Components/Transitions/FadeTransition.vue';
import FileableForm from '@/Components/AdminForms/Special/FileableForm.vue';
import ModalHelperButton from '@/Components/Buttons/ModalHelperButton.vue';
import IFluentDocumentTableSearch24Regular from '~icons/fluent/document-table-search-24-regular';

const emit = defineEmits(['close']);

const props = defineProps<{
  fileable?: FileableFormData;
  show: boolean;
}>();

// Fileable type display names
const typeDisplayNames: Record<string, string> = {
  Meeting: 'Posėdis',
  Institution: 'Institucija',
  Duty: 'Pareigos',
  Type: 'Tipas',
};

// Modal title with context when fileable is provided
const modalTitle = computed(() => {
  if (!props.fileable) {
    return $t('Įkelti naują failą');
  }

  const typeName = typeDisplayNames[props.fileable.type] || props.fileable.type;
  const fileableName = (props.fileable as any).fileable_name;

  if (fileableName) {
    return `${$t('Įkelti failą')}: ${fileableName}`;
  }

  return `${$t('Įkelti failą')} (${typeName})`;
});

// Ensure we only use id and type to prevent serialization issues with forceFormData
const sanitizedFileable = computed<FileableFormData | null>(() => {
  if (!props.fileable) return null;
  return { id: props.fileable.id, type: props.fileable.type };
});

// Determine initial step based on whether fileable is provided
const current = ref(props.fileable ? 2 : 1);
const loading = ref(false);
const showAlert = useStorage('new-file-button-alert', true);

// Bridge current step for Stepper v-model (read-only display)
const stepperStep = computed({
  get: () => current.value,
  set: () => {},
});

const keepFileable = inject<boolean>('keepFileable', false);

const fileForm = useForm<{ fileable: FileableFormData | null; file: any }>({
  fileable: sanitizedFileable.value,
  file: null,
});

// Reset state when modal closes, so next open starts fresh
watch(() => props.show, (isShowing) => {
  if (!isShowing) {
    // Modal closed - reset for next open
    current.value = props.fileable ? 2 : 1;
    fileForm.fileable = sanitizedFileable.value;
    fileForm.file = null;
  }
});

const handleFileableSubmit = (model: { id: string; fileable_name: string; type: string }) => {
  // FileableForm returns id as string, include fileable_name for name generation
  fileForm.fileable = { id: model.id, type: model.type, fileable_name: model.fileable_name };
  current.value = 2;
};

const handleFileSubmit = (file: any) => {
  fileForm.file = file;
  submitFullForm();
};

const submitFullForm = () => {
  loading.value = true;
  fileForm.post(route('sharepointFiles.store'), {
    forceFormData: true, // Ensure FormData is used for file uploads
    onSuccess: () => {
      emit('close');
      if (!keepFileable) {
        fileForm.reset();
        current.value = 1;
      }
      else {
        current.value = 2;
        fileForm.file = null;
      }
    },
    onFinish: () => {
      loading.value = false;
    },
  });
};
</script>
