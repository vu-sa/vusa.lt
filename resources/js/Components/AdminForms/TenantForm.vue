<template>
  <AdminForm :model="form" label-placement="top" @submit:form="$emit('submit:form', form)" @delete="$emit('delete')">
    <FormElement>
      <template #title>
        {{ $t("forms.context.main_info") }}
      </template>
      <NFormItem label="Pavadinimas" :required="true">
        <NInput v-model:value="form.fullname" />
      </NFormItem>
      <NFormItem label="Trumpinys" :required="true">
        <NInput v-model:value="form.shortname" />
      </NFormItem>
      <NFormItem label="Tipas" :required="true">
        <NSelect v-model:value="form.type" :options="typeOptions" />
      </NFormItem>
      <NFormItem label="Alias">
        <NInput v-model:value="form.alias" />
      </NFormItem>
      <NFormItem label="Trumpinys VU">
        <NInput v-model:value="form.shortname_vu" />
      </NFormItem>
      <NFormItem label="Pagrindinė įstaiga">
        <NSelect v-model:value="form.primary_institution_id" filterable :options="institutionOptions" clearable />
      </NFormItem>
    </FormElement>
  </AdminForm>
</template>

<script setup lang="tsx">
import { useForm } from "@inertiajs/vue3";

import FormElement from "./FormElement.vue";
import AdminForm from "./AdminForm.vue";

const { tenant, assignableInstitutions, rememberKey } = defineProps<{
  tenant: App.Entities.Tenant;
  assignableInstitutions: Array<App.Entities.Institution>;
  rememberKey?: "CreateTenant";
}>();

defineEmits<{
  (event: "submit:form", form: unknown): void;
  (event: "delete"): void;
}>();

const form = rememberKey ? useForm(rememberKey, tenant) : useForm(tenant);

const typeOptions = [
  { label: "PKP", value: "pkp" },
  { label: "Padalinys", value: "padalinys" },
  { label: "Pagrindinis", value: "pagrindinis" },
];

const institutionOptions = assignableInstitutions.map((institution) => ({
  label: institution.name,
  value: institution.id,
}));
</script>
