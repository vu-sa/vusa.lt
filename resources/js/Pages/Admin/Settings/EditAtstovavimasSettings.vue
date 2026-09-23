<template>
  <FormPage
    :title="$t('settings.pages.atstovavimas.title')"
    :back-href="route('settings.index')"
    :back-label="$t('settings.title')"
    :processing="form.processing"
    :dirty="form.isDirty"
    :errors="form.errors"
    :available-locales="[]"
    @submit="handleFormSubmit"
  >
    <FormSection
      :title="$t('settings.atstovavimas_settings.manager_role_title')"
      :description="$t('settings.atstovavimas_settings.manager_role_description')"
    >
      <FormFieldWrapper
        id="institution_manager_role_id"
        :label="$t('settings.atstovavimas_settings.manager_role_label')"
        :hint="$t('settings.atstovavimas_settings.manager_role_note')"
        :error="form.errors.institution_manager_role_id"
      >
        <Select v-model="form.institution_manager_role_id">
          <SelectTrigger id="institution_manager_role_id">
            <SelectValue :placeholder="$t('settings.atstovavimas_settings.manager_role_placeholder')" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem v-for="option in roleOptions" :key="option.value" :value="option.value">
              {{ option.label }}
            </SelectItem>
          </SelectContent>
        </Select>
      </FormFieldWrapper>
    </FormSection>

    <FormSection
      :title="$t('settings.atstovavimas_settings.student_rep_type_title')"
      :description="$t('settings.atstovavimas_settings.student_rep_type_description')"
    >
      <FormFieldWrapper
        id="student_rep_root_type_id"
        :label="$t('settings.atstovavimas_settings.student_rep_type_label')"
        :hint="$t('settings.atstovavimas_settings.student_rep_type_note')"
        :error="form.errors.student_rep_root_type_id"
      >
        <Select v-model="form.student_rep_root_type_id">
          <SelectTrigger id="student_rep_root_type_id">
            <SelectValue :placeholder="$t('settings.atstovavimas_settings.student_rep_type_placeholder')" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem :value="null">
              {{ $t('settings.atstovavimas_settings.student_rep_type_default') }}
            </SelectItem>
            <SelectItem v-for="option in typeOptions" :key="option.value" :value="option.value">
              {{ option.label }}
            </SelectItem>
          </SelectContent>
        </Select>
      </FormFieldWrapper>
    </FormSection>
  </FormPage>
</template>

<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

import FormFieldWrapper from '@/Components/AdminForms/FormFieldWrapper.vue';
import FormPage from '@/Components/Layouts/FormPage.vue';
import FormSection from '@/Components/Patterns/FormSection.vue';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';

interface Role {
  id: string;
  name: string;
}

interface InstitutionType {
  id: number;
  title: string;
  slug: string;
}

const props = withDefaults(defineProps<{
  institution_manager_role_id: string | null;
  student_rep_root_type_id?: number | null;
  roles: Role[];
  institution_types?: InstitutionType[];
}>(), {
  student_rep_root_type_id: null,
  institution_types: () => [],
});

const roleOptions = computed(() =>
  props.roles.map(role => ({
    label: role.name,
    value: role.id,
  })),
);

const typeOptions = computed(() =>
  props.institution_types.map(type => ({
    label: `${type.title} (${type.slug})`,
    value: type.id,
  })),
);

const form = useForm({
  institution_manager_role_id: props.institution_manager_role_id,
  student_rep_root_type_id: props.student_rep_root_type_id,
});

const handleFormSubmit = () => {
  form.post(route('settings.atstovavimas.update'));
};
</script>
