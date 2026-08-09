<template>
  <AdminForm :model="form" label-placement="top" @submit:form="$emit('submit:form', form)" @delete="$emit('delete')">
    <FormElement>
      <template #title>
        {{ $t('forms.context.main_info') }}
      </template>

      <FormFieldWrapper id="name" :label="$t('surveys.fields.name')" required :error="form.errors.name">
        <MultiLocaleInput v-model:input="form.name" />
      </FormFieldWrapper>

      <FormFieldWrapper id="description" :label="$t('surveys.fields.description')" :error="form.errors.description">
        <MultiLocaleInput v-model:input="form.description" />
      </FormFieldWrapper>

      <FormFieldWrapper
        id="welcome_text"
        :label="$t('surveys.fields.welcome_text')"
        :error="form.errors.welcome_text"
      >
        <MultiLocaleInput v-model:input="form.welcome_text" />
      </FormFieldWrapper>

      <FormFieldWrapper id="tenant_id" :label="$t('surveys.fields.tenant')" required :error="form.errors.tenant_id">
        <SingleSelect
          v-model="selectedTenant"
          :options="tenantOptions"
          label-field="label"
          value-field="value"
        />
      </FormFieldWrapper>
    </FormElement>

    <FormElement>
      <template #title>
        {{ $t('surveys.fields.starts_at') }} / {{ $t('surveys.fields.ends_at') }}
      </template>

      <FormFieldWrapper id="starts_at" :label="$t('surveys.fields.starts_at')" :error="form.errors.starts_at">
        <DateTimePicker v-model="form.starts_at" :minute-step="5" />
      </FormFieldWrapper>

      <FormFieldWrapper id="ends_at" :label="$t('surveys.fields.ends_at')" :error="form.errors.ends_at">
        <DateTimePicker v-model="form.ends_at" :minute-step="5" />
      </FormFieldWrapper>

      <FormFieldWrapper id="is_anonymous" :label="$t('surveys.fields.is_anonymous')" :error="form.errors.is_anonymous">
        <div class="flex items-center gap-2">
          <Switch id="is_anonymous" v-model="form.is_anonymous" />
          <span class="text-sm text-zinc-600 dark:text-zinc-400">
            {{ $t('surveys.helpers.anonymous') }}
          </span>
        </div>
      </FormFieldWrapper>
    </FormElement>
  </AdminForm>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

import MultiLocaleInput from '../FormItems/MultiLocaleInput.vue';

import AdminForm from './AdminForm.vue';
import FormElement from './FormElement.vue';
import FormFieldWrapper from './FormFieldWrapper.vue';

import { DateTimePicker } from '@/Components/ui/date-picker';
import { SingleSelect } from '@/Components/ui/single-select';
import { Switch } from '@/Components/ui/switch';

const { survey, assignableTenants } = defineProps<{
  survey: Record<string, unknown>;
  assignableTenants: App.Entities.Tenant[];
}>();

defineEmits<{
  (event: 'submit:form', form: unknown): void;
  (event: 'delete'): void;
}>();

const form = useForm('Survey', {
  id: survey.id ?? null,
  tenant_id: survey.tenant_id ?? null,
  name: survey.name ?? { lt: '', en: '' },
  description: survey.description ?? { lt: '', en: '' },
  welcome_text: survey.welcome_text ?? { lt: '', en: '' },
  starts_at: survey.starts_at ?? null,
  ends_at: survey.ends_at ?? null,
  is_anonymous: survey.is_anonymous ?? true,
});

const tenantOptions = computed(() =>
  assignableTenants.map(tenant => ({ label: tenant.shortname, value: tenant.id })),
);

// Bridge: SingleSelect operates on full objects, form stores tenant_id for server submission
const selectedTenant = computed({
  get: () => tenantOptions.value.find(t => t.value === form.tenant_id) ?? null,
  set: (val: { label: string; value: number } | null) => {
    form.tenant_id = val?.value ?? null;
  },
});
</script>
