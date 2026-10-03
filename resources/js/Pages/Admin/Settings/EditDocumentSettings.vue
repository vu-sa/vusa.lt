<template>
  <FormPage
    :title="$t('settings.pages.documents.title')"
    :back-href="route('settings.index')"
    :back-label="$t('settings.title')"
    :processing="form.processing"
    :dirty="form.isDirty"
    :errors="form.errors"
    :available-locales="[]"
    @submit="handleFormSubmit"
  >
    <FormSection
      :title="$t('settings.document_settings.important_types_title')"
      :description="$t('settings.document_settings.important_types_description')"
    >
      <FormFieldWrapper
        id="important_content_types"
        :label="$t('settings.document_settings.important_types_label')"
        :error="form.errors.important_content_types"
      >
        <MultiSelect
          v-model="selectedTypes"
          :options="contentTypeOptions"
          label-field="label"
          value-field="value"
          :placeholder="$t('settings.document_settings.important_types_placeholder')"
          :empty-text="$t('settings.document_settings.no_types_found')"
        />
      </FormFieldWrapper>
    </FormSection>
  </FormPage>
</template>

<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ref, watch, computed } from 'vue';

import FormFieldWrapper from '@/Components/AdminForms/FormFieldWrapper.vue';
import FormPage from '@/Components/Layouts/FormPage.vue';
import FormSection from '@/Components/Patterns/FormSection.vue';
import { MultiSelect } from '@/Components/ui/multi-select';

interface ContentTypeOption {
  value: string;
  label: string;
}

const props = defineProps<{
  selected_content_types: string[];
  available_content_types: string[];
}>();

// Transform available content types to options format
const contentTypeOptions = computed<ContentTypeOption[]>(() => {
  return props.available_content_types.map(type => ({
    value: type,
    label: type,
  }));
});

// Initialize selected types from props
const selectedTypes = ref<ContentTypeOption[]>(
  props.selected_content_types.map(type => ({
    value: type,
    label: type,
  })),
);

const form = useForm({
  important_content_types: props.selected_content_types,
});

// Sync selected types to form
watch(selectedTypes, (newTypes) => {
  form.important_content_types = newTypes.map(type => type.value);
}, { deep: true });

const handleFormSubmit = () => {
  form.post(route('settings.documents.update'));
};
</script>
