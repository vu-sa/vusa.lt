<template>
  <FormPage
    :title="$t('settings.pages.meetings.title')"
    :back-href="route('settings.index')"
    :back-label="$t('settings.title')"
    :processing="form.processing"
    :dirty="form.isDirty"
    :errors="form.errors"
    :available-locales="[]"
    @submit="handleFormSubmit"
  >
    <FormSection
      :title="$t('settings.meeting_settings.types_title')"
      :description="$t('settings.meeting_settings.types_description')"
    >
      <FormFieldWrapper id="type_ids" :label="$t('settings.meeting_settings.types_label')" :error="form.errors.type_ids">
        <MultiSelect
          v-model="selectedTypes"
          :options="availablePublicTypes"
          label-field="title"
          value-field="id"
          :placeholder="$t('settings.meeting_settings.types_placeholder')"
          :empty-text="$t('settings.meeting_settings.no_types_found')"
        />
      </FormFieldWrapper>
    </FormSection>

    <FormSection
      :title="$t('settings.meeting_settings.excluded_types_title')"
      :description="$t('settings.meeting_settings.excluded_types_description')"
    >
      <FormFieldWrapper
        id="excluded_type_ids"
        :label="$t('settings.meeting_settings.excluded_types_label')"
        :error="form.errors.excluded_type_ids"
      >
        <MultiSelect
          v-model="excludedTypes"
          :options="availableExcludedTypes"
          label-field="title"
          value-field="id"
          :placeholder="$t('settings.meeting_settings.excluded_types_placeholder')"
          :empty-text="$t('settings.meeting_settings.no_types_found')"
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

interface InstitutionType {
  id: number;
  title: string;
  slug: string;
}

const props = defineProps<{
  selected_type_ids: number[];
  excluded_type_ids: number[];
  available_types: InstitutionType[];
}>();

// Initialize selected types from props
const selectedTypes = ref<InstitutionType[]>(
  props.available_types.filter(type => props.selected_type_ids.includes(type.id)),
);

// Initialize excluded types from props
const excludedTypes = ref<InstitutionType[]>(
  props.available_types.filter(type => props.excluded_type_ids.includes(type.id)),
);

// Compute available options for public types (exclude already excluded types)
const availablePublicTypes = computed(() => {
  const excludedIds = excludedTypes.value.map(t => t.id);
  return props.available_types.filter(type => !excludedIds.includes(type.id));
});

// Compute available options for excluded types (exclude already selected public types)
const availableExcludedTypes = computed(() => {
  const selectedIds = selectedTypes.value.map(t => t.id);
  return props.available_types.filter(type => !selectedIds.includes(type.id));
});

const form = useForm({
  type_ids: props.selected_type_ids,
  excluded_type_ids: props.excluded_type_ids,
});

// Sync selected types to form
watch(selectedTypes, (newTypes) => {
  form.type_ids = newTypes.map(type => type.id);
}, { deep: true });

// Sync excluded types to form
watch(excludedTypes, (newTypes) => {
  form.excluded_type_ids = newTypes.map(type => type.id);
}, { deep: true });

const handleFormSubmit = () => {
  form.post(route('settings.meetings.update'));
};
</script>
