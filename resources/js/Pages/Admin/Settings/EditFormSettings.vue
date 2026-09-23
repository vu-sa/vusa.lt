<template>
  <FormPage
    :title="$t('settings.pages.forms.title')"
    :back-href="route('settings.index')"
    :back-label="$t('settings.title')"
    :processing="form.processing"
    :dirty="form.isDirty"
    :errors="form.errors"
    :available-locales="[]"
    @submit="handleFormSubmit"
  >
    <FormSection
      :title="$t('settings.form_settings.registration_form_title')"
      :description="$t('settings.form_settings.registration_form_description')"
    >
      <FormFieldWrapper
        id="member_registration_form_id"
        :label="$t('settings.form_settings.form_label')"
        :error="form.errors.member_registration_form_id"
      >
        <Select v-model="form.member_registration_form_id">
          <SelectTrigger id="member_registration_form_id">
            <SelectValue :placeholder="$t('settings.form_settings.form_placeholder')" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem v-for="formOption in forms" :key="formOption.id" :value="formOption.id">
              {{ formOption.name }}
            </SelectItem>
          </SelectContent>
        </Select>
      </FormFieldWrapper>

      <FormFieldWrapper
        id="member_registration_notification_recipient_role_id"
        :label="$t('settings.form_settings.role_label')"
        :error="form.errors.member_registration_notification_recipient_role_id"
      >
        <Select v-model="form.member_registration_notification_recipient_role_id">
          <SelectTrigger id="member_registration_notification_recipient_role_id">
            <SelectValue :placeholder="$t('settings.form_settings.role_placeholder')" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem v-for="role in roles" :key="role.id" :value="role.id">
              {{ role.name }}
            </SelectItem>
          </SelectContent>
        </Select>
      </FormFieldWrapper>
    </FormSection>

    <FormSection
      :title="$t('settings.form_settings.student_rep_title')"
      :description="$t('settings.form_settings.student_rep_description')"
    >
      <FormFieldWrapper
        id="student_rep_registration_form_id"
        :label="$t('settings.form_settings.student_rep_form_label')"
        :error="form.errors.student_rep_registration_form_id"
      >
        <Select v-model="form.student_rep_registration_form_id">
          <SelectTrigger id="student_rep_registration_form_id">
            <SelectValue :placeholder="$t('settings.form_settings.form_placeholder')" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem :value="null">
              {{ $t('settings.form_settings.no_form_selected') }}
            </SelectItem>
            <SelectItem v-for="formOption in forms" :key="formOption.id" :value="formOption.id">
              {{ formOption.name }}
            </SelectItem>
          </SelectContent>
        </Select>
      </FormFieldWrapper>

      <FormFieldWrapper
        id="student_rep_institution_type_ids"
        :label="$t('settings.form_settings.student_rep_types_label')"
        :hint="$t('settings.form_settings.student_rep_types_description')"
        :error="form.errors.student_rep_institution_type_ids"
      >
        <MultiSelect
          v-model="selectedTypes"
          :options="props.institution_types"
          label-field="title"
          value-field="id"
          :placeholder="$t('settings.form_settings.student_rep_types_placeholder')"
          :empty-text="$t('settings.form_settings.no_types_found')"
        />
      </FormFieldWrapper>
    </FormSection>
  </FormPage>
</template>

<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

import FormFieldWrapper from '@/Components/AdminForms/FormFieldWrapper.vue';
import FormPage from '@/Components/Layouts/FormPage.vue';
import FormSection from '@/Components/Patterns/FormSection.vue';
import { MultiSelect } from '@/Components/ui/multi-select';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/Components/ui/select';

interface InstitutionType {
  id: number;
  title: string;
  slug: string;
}

const props = defineProps<{
  member_registration_form_id: string | null;
  member_registration_notification_recipient_role_id: string | null;
  student_rep_registration_form_id: string | null;
  student_rep_institution_type_ids: number[];
  forms: App.Entities.Form[];
  roles: App.Entities.Role[];
  institution_types: InstitutionType[];
}>();

// Initialize selected types from props
const selectedTypes = ref<InstitutionType[]>(
  props.institution_types.filter(type => props.student_rep_institution_type_ids.includes(type.id)),
);

const form = useForm({
  member_registration_form_id: props.member_registration_form_id,
  member_registration_notification_recipient_role_id: props.member_registration_notification_recipient_role_id,
  student_rep_registration_form_id: props.student_rep_registration_form_id,
  student_rep_institution_type_ids: props.student_rep_institution_type_ids ?? [],
});

// Sync selected types to form
watch(selectedTypes, (newTypes) => {
  form.student_rep_institution_type_ids = newTypes.map(type => type.id);
}, { deep: false });

const handleFormSubmit = () => {
  form.post(route('settings.forms.update'));
};
</script>
