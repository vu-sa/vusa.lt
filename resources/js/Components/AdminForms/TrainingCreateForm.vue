<template>
  <AdminForm :model="form" label-placement="top" @submit:form="$emit('submit:form', form)" @delete="$emit('delete')">
    <FormElement>
      <template #title>
        {{ $t("forms.context.main_info") }}
      </template>
      <template #description>
        <p>
          {{ $t('forms.helpers.training_create_info_1') }}
        </p>
        <p>
          {{ $t('forms.helpers.training_create_info_2') }}
        </p>
      </template>
      <FormFieldWrapper id="name" :label="$t('forms.fields.name')" required :error="form.errors.name">
        <MultiLocaleInput v-model:input="form.name" />
      </FormFieldWrapper>
      <div class="space-y-2">
        <div class="inline-flex items-center gap-2">
          <Label for="description">{{ $t('forms.fields.description') }}</Label>
          <span class="text-red-500">*</span>
          <SimpleLocaleButton v-model:locale="locale" />
        </div>
        <TiptapEditor v-if="locale === 'lt'" v-model="form.description.lt" preset="full" :html="true" />
        <TiptapEditor v-else v-model="form.description.en" preset="full" :html="true" />
        <p v-if="form.errors.description" class="text-xs text-red-600 dark:text-red-400">
          {{ form.errors.description }}
        </p>
      </div>
      <FormFieldWrapper id="institution_id" :label="$t('Kas organizuoja mokymus?')" required :error="form.errors.institution_id">
        <SingleSelect
          v-model="selectedInstitution"
          :options="institutionOptions"
          label-field="label"
          value-field="value"
          :placeholder="$t('Pasirinkite instituciją')"
          :empty-text="$t('Nerasta institucijų')"
        />
      </FormFieldWrapper>
      <FormFieldWrapper id="start_time" :label="$t('forms.fields.training_start_time')" required :error="form.errors.start_time">
        <DateTimePicker v-model="form.start_time" :hour-range="[8, 22]" :minute-step="5" />
      </FormFieldWrapper>
    </FormElement>
  </AdminForm>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

import MultiLocaleInput from '../FormItems/MultiLocaleInput.vue';
import SimpleLocaleButton from '../Buttons/SimpleLocaleButton.vue';

import FormElement from './FormElement.vue';
import FormFieldWrapper from './FormFieldWrapper.vue';
import AdminForm from './AdminForm.vue';

import { DateTimePicker } from '@/Components/ui/date-picker';
import { Label } from '@/Components/ui/label';
import { SingleSelect } from '@/Components/ui/single-select';
import TiptapEditor from '@/Components/TipTap/TiptapEditor.vue';

const { training } = defineProps<{
  training: App.Entities.Membership;
}>();

defineEmits<{
  (event: 'submit:form', form: unknown): void;
  (event: 'delete'): void;
}>();

const form = useForm('CreateTraining', training);
const locale = ref('lt');

// Build searchable institution options from user's current duties
const institutionOptions = computed(() => {
  const duties = usePage().props.auth?.user?.current_duties ?? [];
  const mapped = duties
    .map((duty: any) => {
      if (!duty.institution) return null;
      return {
        label: duty.institution.name as string,
        value: duty.institution.id as number,
      };
    })
    .filter((item: { label: string; value: number } | null): item is { label: string; value: number } => item !== null);

  // Dedupe by value
  return mapped.filter((value, index, self) =>
    self.findIndex(t => t.value === value.value) === index,
  );
});

// Bridge: SingleSelect operates on full objects, form stores institution_id for server submission
const selectedInstitution = computed({
  get: () => institutionOptions.value.find(i => i.value === form.institution_id) ?? null,
  set: (val: { label: string; value: number } | null) => { form.institution_id = val?.value ?? null; },
});
</script>
