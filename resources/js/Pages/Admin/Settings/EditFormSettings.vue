<template>
  <FormPage
    :mode="canEdit ? 'edit' : 'view'"
    :title="$t('settings.pages.forms.title')"
    :back-href="route('settings.index')"
    :back-label="$t('settings.title')"
    :processing="form.processing"
    :dirty="form.isDirty"
    :errors="form.errors"
    :available-locales="[]"
    required-note
    @submit="handleFormSubmit"
  >
    <FormSection
      :title="$t('settings.form_settings.registration_form_title')"
      :description="$t('settings.form_settings.registration_form_description')"
    >
      <FormFieldWrapper
        id="member_registration_form_id"
        required
        :label="$t('settings.form_settings.form_label')"
        :error="form.errors.member_registration_form_id"
      >
        <NativeSelect
          id="member_registration_form_id"
          v-model="form.member_registration_form_id"
          :options="formOptions"
          :placeholder="$t('settings.form_settings.form_placeholder')"
        />
      </FormFieldWrapper>

      <div class="space-y-1 border-t border-border pt-4 text-sm" data-testid="registration-recipients">
        <p class="font-medium text-foreground">
          {{ $t('settings.form_settings.recipients_title') }}
        </p>
        <template v-if="memberRegistration.responsibility">
          <p class="text-muted-foreground">
            {{ $t('settings.form_settings.recipients_description') }}
            <Link :href="route('responsibilities.show', memberRegistration.responsibility.id)" class="underline underline-offset-4">
              {{ $t('settings.form_settings.recipients_link') }}
            </Link>
          </p>
          <p v-if="memberRegistration.gaps.length" class="text-status-attention">
            {{ $t('settings.form_settings.recipients_gaps', { tenants: memberRegistration.gaps.join(', ') }) }}
          </p>
        </template>
        <p v-else class="text-status-attention">
          {{ $t('settings.form_settings.recipients_missing') }}
        </p>
      </div>
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
        <NativeSelect
          id="student_rep_registration_form_id"
          placeholder-selectable
          :placeholder-value="null"
          v-model="form.student_rep_registration_form_id"
          :options="formOptions"
          :placeholder="$t('settings.form_settings.no_form_selected')"
        />
      </FormFieldWrapper>

      <FormFieldWrapper
        id="student_rep_institution_type_ids"
        :label="$t('settings.form_settings.student_rep_types_label')"
        :hint="$t('settings.form_settings.student_rep_types_description')"
        :error="form.errors.student_rep_institution_type_ids"
      >
        <TypeTreeMultiSelect
          v-model="form.student_rep_institution_type_ids"
          :types="props.institution_types"
          :placeholder="$t('settings.form_settings.student_rep_types_placeholder')"
          :empty-text="$t('settings.form_settings.no_types_found')"
        />
      </FormFieldWrapper>
    </FormSection>
  </FormPage>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { Link, useForm } from '@inertiajs/vue3';

import FormFieldWrapper from '@/Components/AdminForms/FormFieldWrapper.vue';
import FormPage from '@/Components/Layouts/FormPage.vue';
import FormSection from '@/Components/Patterns/FormSection.vue';
import { TypeTreeMultiSelect } from '@/Components/AdminForms/TypeTree';
import NativeSelect from '@/Components/ui/native-select/NativeSelect.vue';

interface InstitutionType {
  id: number;
  title: string;
  slug: string;
  parent_id: number | null;
}

const props = defineProps<{
  canEdit: boolean;
  member_registration_form_id: string | null;
  student_rep_registration_form_id: string | null;
  student_rep_institution_type_ids: number[];
  forms: App.Entities.Form[];
  memberRegistration: { responsibility: { id: string; title: string } | null; gaps: string[] };
  institution_types: InstitutionType[];
}>();

const form = useForm({
  member_registration_form_id: props.member_registration_form_id,
  student_rep_registration_form_id: props.student_rep_registration_form_id,
  student_rep_institution_type_ids: props.student_rep_institution_type_ids ?? [],
});

const formOptions = computed(() =>
  props.forms.map(f => ({
    label: f.name,
    value: f.id,
  })),
);

const handleFormSubmit = () => {
  form.post(route('settings.forms.update'));
};
</script>
