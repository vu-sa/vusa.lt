<template>
  <AdminForm :model="form" label-placement="top" @submit:form="$emit('submit:form', form)" @delete="$emit('delete')">
    <FormElement>
      <template #title>
        {{ $t("forms.context.main_info") }}
      </template>
      <template #description>
        <p class="mb-4">
          Studijų programa – tai konkreti studijų kryptis, kuriai priskiriami pareigūnai (pvz., studentų atstovai).
        </p>
      </template>

      <FormFieldWrapper id="name" :label="$t('forms.fields.title')" required>
        <MultiLocaleInput v-model:input="form.name" />
      </FormFieldWrapper>

      <div class="grid gap-x-4 lg:grid-cols-2">
        <FormFieldWrapper id="degree" label="Laipsnis" required>
          <Select v-model="form.degree">
            <SelectTrigger>
              <SelectValue placeholder="Pasirinkite laipsnį" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem v-for="opt in degreeOptions" :key="opt.value" :value="opt.value">
                {{ opt.label }}
              </SelectItem>
            </SelectContent>
          </Select>
        </FormFieldWrapper>

        <FormFieldWrapper id="tenant_id" label="Padalinys" required>
          <Select v-model="tenantIdString">
            <SelectTrigger>
              <SelectValue placeholder="Pasirinkite padalinį" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem v-for="opt in tenantOptions" :key="opt.value" :value="String(opt.value)">
                {{ opt.label }}
              </SelectItem>
            </SelectContent>
          </Select>
        </FormFieldWrapper>
      </div>
    </FormElement>
  </AdminForm>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { useForm } from '@inertiajs/vue3';

import FormElement from './FormElement.vue';
import FormFieldWrapper from './FormFieldWrapper.vue';
import AdminForm from './AdminForm.vue';

import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import MultiLocaleInput from '@/Components/FormItems/MultiLocaleInput.vue';
import { getDegreeOptions } from '@/Utils/Degrees';

const { studyProgram, tenants, rememberKey } = defineProps<{
  studyProgram: App.Entities.StudyProgram;
  tenants: Array<App.Entities.Tenant>;
  rememberKey?: 'CreateStudyProgram';
}>();

defineEmits<{
  (event: 'submit:form', form: unknown): void;
  (event: 'delete'): void;
}>();

const form = rememberKey ? useForm(rememberKey, studyProgram) : useForm(studyProgram);

const degreeOptions = getDegreeOptions();

const tenantOptions = tenants.map(tenant => ({
  label: tenant.shortname,
  value: tenant.id,
}));

// Shadcn Select requires string values
const tenantIdString = computed({
  get: () => form.tenant_id != null ? String(form.tenant_id) : '',
  set: (val: string) => { form.tenant_id = val ? Number(val) : null; },
});
</script>
