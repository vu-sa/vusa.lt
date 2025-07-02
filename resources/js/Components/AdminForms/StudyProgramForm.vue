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
      
      <NFormItem :label="$t('forms.fields.title')" :required="true">
        <MultiLocaleInput v-model:input="form.name" />
      </NFormItem>

      <div class="grid gap-x-4 lg:grid-cols-2">
        <NFormItem label="Laipsnis" :required="true">
          <NSelect v-model:value="form.degree" :options="degreeOptions" placeholder="Pasirinkite laipsnį" />
        </NFormItem>

        <NFormItem label="Padalinys" :required="true">
          <NSelect v-model:value="form.tenant_id" :options="tenantOptions" placeholder="Pasirinkite padalinį" />
        </NFormItem>
      </div>
    </FormElement>
  </AdminForm>
</template>

<script setup lang="ts">
import { useForm } from "@inertiajs/vue3";

import FormElement from "./FormElement.vue";
import AdminForm from "./AdminForm.vue";
import MultiLocaleInput from "@/Components/FormItems/MultiLocaleInput.vue";
import { getDegreeOptions } from "@/Utils/Degrees";

const { studyProgram, tenants, rememberKey } = defineProps<{
  studyProgram: App.Entities.StudyProgram;
  tenants: Array<App.Entities.Tenant>;
  rememberKey?: "CreateStudyProgram";
}>();

defineEmits<{
  (event: "submit:form", form: unknown): void;
  (event: "delete"): void;
}>();

const form = rememberKey ? useForm(rememberKey, studyProgram) : useForm(studyProgram);

const degreeOptions = getDegreeOptions();

const tenantOptions = tenants.map((tenant) => ({
  label: tenant.shortname,
  value: tenant.id,
}));
</script>
