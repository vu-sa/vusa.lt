<template>
  <FormPage
    :mode="canEdit ? 'edit' : 'view'"
    :title="$t('settings.pages.atstovavimas.title')"
    :back-href="route('settings.index')"
    :back-label="$t('settings.title')"
    :processing="form.processing"
    :dirty="form.isDirty"
    :errors="form.errors"
    :available-locales="[]"
    @submit="handleFormSubmit"
  >
    <FormSection :title="$t('settings.atstovavimas_settings.baseline_title')">
      <FormFieldWrapper
        id="baseline_cadence_id"
        :label="$t('settings.atstovavimas_settings.baseline_label')"
        :hint="$t('settings.atstovavimas_settings.baseline_hint')"
        :error="form.errors.baseline_cadence_id"
      >
        <NativeSelect
          id="baseline_cadence_id"
          v-model="form.baseline_cadence_id"
          :disabled="!canEdit"
          :options="cadenceOptions"
          :placeholder="$t('settings.atstovavimas_settings.baseline_automatic')"
          :placeholder-value="null"
          placeholder-selectable
        />
      </FormFieldWrapper>
    </FormSection>
    <FormSection
      :title="$t('settings.atstovavimas_settings.counted_scopes_title')"
      :description="$t('settings.atstovavimas_settings.counted_scopes_description')"
    >
      <FormFieldWrapper
        id="counted_institution_scopes"
        :label="$t('settings.atstovavimas_settings.counted_scopes_label')"
        :error="form.errors.counted_institution_scopes"
      >
        <fieldset :disabled="!canEdit" class="divide-y divide-border">
          <label
            v-for="scope in institution_scopes"
            :key="scope.value"
            class="flex min-h-11 cursor-pointer items-start gap-3 py-2 text-sm"
            :data-testid="`counted-scope-${scope.value}`"
          >
            <Checkbox
              class="mt-0.5"
              :model-value="form.counted_institution_scopes.includes(scope.value)"
              @update:model-value="toggleScope(scope.value, $event === true)"
            />
            <span class="space-y-0.5">
              <span class="block font-medium">{{ scope.label }}</span>
              <span class="block text-xs text-muted-foreground">{{ $t(`settings.atstovavimas_settings.counted_scopes.${scope.value}`) }}</span>
            </span>
          </label>
        </fieldset>
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
        <TypeTreeSelect
          id="student_rep_root_type_id"
          v-model="form.student_rep_root_type_id"
          :types="institution_types"
          :disabled="!canEdit"
          :placeholder="$t('settings.atstovavimas_settings.student_rep_type_placeholder')"
          placeholder-selectable
        />
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
import { NativeSelect } from '@/Components/ui/native-select';
import { formatDate } from '@/Utils/dateTime';
import { Checkbox } from '@/Components/ui/checkbox';
import { TypeTreeSelect } from '@/Components/AdminForms/TypeTree';

interface InstitutionType {
  id: number;
  title: string;
  slug: string;
  parent_id: number | null;
}

const props = withDefaults(defineProps<{
  canEdit: boolean;
  baseline_cadence_id?: string | null;
  baseline_cadences?: { id: string; label: string; start_date: string; end_date: string }[];
  student_rep_root_type_id?: number | null;
  institution_types?: InstitutionType[];
  counted_institution_scopes?: string[];
  institution_scopes?: { value: string; label: string }[];
}>(), {
  baseline_cadence_id: null,
  baseline_cadences: () => [],
  student_rep_root_type_id: null,
  institution_types: () => [],
  counted_institution_scopes: () => ['vu'],
  institution_scopes: () => [],
});

const cadenceOptions = computed(() => props.baseline_cadences.map(cadence => ({
  value: cadence.id, label: `${cadence.label} · ${formatDate(cadence.start_date)} – ${formatDate(cadence.end_date)}`,
})));

const form = useForm({
  baseline_cadence_id: props.baseline_cadence_id,
  student_rep_root_type_id: props.student_rep_root_type_id,
  counted_institution_scopes: [...props.counted_institution_scopes],
});

function toggleScope(scope: string, checked: boolean): void {
  const rest = form.counted_institution_scopes.filter(value => value !== scope);
  form.counted_institution_scopes = checked ? [...rest, scope] : rest;
}

const handleFormSubmit = () => {
  form.post(route('settings.atstovavimas.update'));
};
</script>
