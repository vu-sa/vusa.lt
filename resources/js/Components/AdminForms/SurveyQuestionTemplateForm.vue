<template>
  <AdminForm :model="form" label-placement="top" @submit:form="$emit('submit:form', form)" @delete="$emit('delete')">
    <FormElement>
      <template #title>
        {{ $t('surveys.question_bank') }}
      </template>

      <FormFieldWrapper id="title" :label="$t('surveys.fields.title')" required :error="form.errors.title">
        <Input id="title" v-model="form.title" />
        <p class="mt-1 text-xs text-zinc-500">{{ $t('surveys.helpers.question_code') }}</p>
      </FormFieldWrapper>

      <FormFieldWrapper id="type" :label="$t('surveys.fields.type')" required :error="form.errors.type">
        <Select v-model="form.type">
          <SelectTrigger id="type">
            <SelectValue />
          </SelectTrigger>
          <SelectContent>
            <SelectItem v-for="type in questionTypes" :key="type.value" :value="type.value">
              {{ type.label }}
            </SelectItem>
          </SelectContent>
        </Select>
      </FormFieldWrapper>

      <FormFieldWrapper id="group_name" :label="$t('surveys.fields.group_name')" required :error="form.errors.group_name">
        <MultiLocaleInput v-model:input="form.group_name" />
      </FormFieldWrapper>

      <FormFieldWrapper id="question" :label="$t('surveys.fields.question')" required :error="form.errors.question">
        <MultiLocaleInput v-model:input="form.question" />
      </FormFieldWrapper>

      <FormFieldWrapper id="help" :label="$t('surveys.fields.help')" :error="form.errors.help">
        <MultiLocaleInput v-model:input="form.help" />
      </FormFieldWrapper>

      <FormFieldWrapper id="tenant_id" :label="$t('surveys.fields.tenant')" :error="form.errors.tenant_id">
        <SingleSelect
          v-model="selectedTenant"
          :options="tenantOptions"
          label-field="label"
          value-field="value"
        />
        <p class="mt-1 text-xs text-zinc-500">{{ $t('surveys.helpers.global_template') }}</p>
      </FormFieldWrapper>

      <div class="flex items-center gap-4">
        <div class="flex items-center gap-2">
          <Switch id="is_required" v-model="form.is_required" />
          <Label for="is_required">{{ $t('surveys.fields.is_required') }}</Label>
        </div>
        <div class="flex items-center gap-2">
          <Switch id="is_active" v-model="form.is_active" />
          <Label for="is_active">{{ $t('surveys.fields.is_active') }}</Label>
        </div>
      </div>
    </FormElement>

    <FormElement v-if="typeHasOptions">
      <template #title>
        {{ $t('surveys.fields.options') }}
      </template>

      <div v-for="(option, index) in form.options" :key="index" class="flex items-start gap-2">
        <Input v-model="option.code" class="w-28" :placeholder="$t('surveys.fields.option_code')" />
        <div class="flex-1">
          <MultiLocaleInput v-model:input="option.label" />
        </div>
        <Button type="button" variant="ghost" size="icon" @click="form.options.splice(index, 1)">
          <Trash2 class="size-4 text-red-600" />
        </Button>
      </div>

      <Button type="button" variant="outline" size="sm" @click="addOption">
        <Plus class="mr-1 size-4" />
        {{ $t('surveys.actions.add_option') }}
      </Button>
    </FormElement>
  </AdminForm>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { useForm } from '@inertiajs/vue3';
import { Plus, Trash2 } from 'lucide-vue-next';
import { computed } from 'vue';

import MultiLocaleInput from '../FormItems/MultiLocaleInput.vue';

import AdminForm from './AdminForm.vue';
import FormElement from './FormElement.vue';
import FormFieldWrapper from './FormFieldWrapper.vue';

import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { SingleSelect } from '@/Components/ui/single-select';
import { Switch } from '@/Components/ui/switch';

const { template, assignableTenants, questionTypes } = defineProps<{
  template: Record<string, any>;
  assignableTenants: App.Entities.Tenant[];
  questionTypes: { value: string; label: string; hasOptions: boolean }[];
}>();

defineEmits<{
  (event: 'submit:form', form: unknown): void;
  (event: 'delete'): void;
}>();

const form = useForm('SurveyQuestionTemplate', {
  id: template.id ?? null,
  tenant_id: template.tenant_id ?? null,
  title: template.title ?? '',
  type: template.type ?? questionTypes[0]?.value ?? 'T',
  group_name: template.group_name ?? { lt: '', en: '' },
  question: template.question ?? { lt: '', en: '' },
  help: template.help ?? { lt: '', en: '' },
  options: template.options ?? [],
  is_required: template.is_required ?? false,
  is_active: template.is_active ?? true,
  order: template.order ?? 0,
});

const typeHasOptions = computed(
  () => questionTypes.find(type => type.value === form.type)?.hasOptions ?? false,
);

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

const addOption = () => {
  form.options.push({ code: `A${form.options.length + 1}`, label: { lt: '', en: '' } });
};
</script>
