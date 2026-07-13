<template>
  <AdminForm :model="form" label-placement="top" @submit:form="$emit('submit:form', form)" @delete="$emit('delete')">
    <FormElement>
      <template #title>
        {{ $t("forms.context.main_info") }}
      </template>
      <FormFieldWrapper id="fullname" :label="$t('forms.fields.name')" required>
        <Input id="fullname" v-model="form.fullname" />
      </FormFieldWrapper>
      <FormFieldWrapper id="shortname" :label="$t('forms.fields.slug')" required>
        <Input id="shortname" v-model="form.shortname" />
      </FormFieldWrapper>
      <FormFieldWrapper id="type" :label="$t('forms.fields.type_label')" required>
        <Select v-model="form.type">
          <SelectTrigger>
            <SelectValue :placeholder="$t('forms.placeholders.select_type')" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem v-for="option in typeOptions" :key="option.value" :value="option.value">
              {{ option.label }}
            </SelectItem>
          </SelectContent>
        </Select>
      </FormFieldWrapper>
      <FormFieldWrapper id="alias" :label="$t('forms.fields.alias')">
        <Input id="alias" v-model="form.alias" />
      </FormFieldWrapper>
      <FormFieldWrapper id="shortname_vu" :label="$t('forms.fields.shortname_vu')">
        <Input id="shortname_vu" v-model="form.shortname_vu" />
      </FormFieldWrapper>
      <FormFieldWrapper id="primary_institution_id" :label="$t('forms.fields.primary_institution')">
        <SingleSelect
          v-model="selectedInstitution"
          :options="assignableInstitutions"
          label-field="name"
          value-field="id"
          :placeholder="$t('forms.placeholders.select_institution')"
        />
      </FormFieldWrapper>
    </FormElement>
  </AdminForm>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { computed } from 'vue';
import { useForm } from '@inertiajs/vue3';

import FormElement from './FormElement.vue';
import FormFieldWrapper from './FormFieldWrapper.vue';
import AdminForm from './AdminForm.vue';

import { SingleSelect } from '@/Components/ui/single-select';
import { Input } from '@/Components/ui/input';

const { tenant, assignableInstitutions, rememberKey } = defineProps<{
  tenant: App.Entities.Tenant;
  assignableInstitutions: Array<App.Entities.Institution>;
  rememberKey?: 'CreateTenant';
}>();

defineEmits<{
  (event: 'submit:form', form: unknown): void;
  (event: 'delete'): void;
}>();

const form = rememberKey ? useForm(rememberKey, tenant) : useForm(tenant);

const typeOptions = computed(() => [
  { label: $t('forms.options.tenant_type_pkp'), value: 'pkp' },
  { label: $t('forms.options.tenant_type_padalinys'), value: 'padalinys' },
  { label: $t('forms.options.tenant_type_pagrindinis'), value: 'pagrindinis' },
]);

// Bridge: SingleSelect operates on full objects, form stores primary_institution_id for server submission
const selectedInstitution = computed({
  get: () => assignableInstitutions.find(i => i.id === form.primary_institution_id) ?? null,
  set: (val: App.Entities.Institution | null) => { form.primary_institution_id = val?.id ?? null; },
});
</script>
