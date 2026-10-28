<template>
  <FormPage
    :mode="canEdit ? 'edit' : 'view'"
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
        <TypeTreeMultiSelect
          v-model="form.type_ids"
          :types="available_types"
          :placeholder="$t('settings.meeting_settings.types_placeholder')"
          :empty-text="$t('settings.meeting_settings.no_types_found')"
        />
      </FormFieldWrapper>
    </FormSection>
  </FormPage>
</template>

<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';

import FormFieldWrapper from '@/Components/AdminForms/FormFieldWrapper.vue';
import FormPage from '@/Components/Layouts/FormPage.vue';
import FormSection from '@/Components/Patterns/FormSection.vue';
import { TypeTreeMultiSelect } from '@/Components/AdminForms/TypeTree';

interface InstitutionType {
  id: number;
  title: string;
  slug: string;
  parent_id: number | null;
}

const props = defineProps<{
  canEdit: boolean;
  selected_type_ids: number[];
  available_types: InstitutionType[];
}>();

const form = useForm({
  type_ids: props.selected_type_ids,
});

const handleFormSubmit = () => {
  form.post(route('settings.meetings.update'));
};
</script>
