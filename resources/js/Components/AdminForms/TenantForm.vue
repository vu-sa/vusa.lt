<template>
  <AdminForm :model="form" label-placement="top" @submit:form="$emit('submit:form', form)" @delete="$emit('delete')">
    <FormElement>
      <template #title>
        {{ $t("forms.context.main_info") }}
      </template>
      <FormFieldWrapper id="fullname" label="Pavadinimas" required>
        <Input id="fullname" v-model="form.fullname" />
      </FormFieldWrapper>
      <FormFieldWrapper id="shortname" label="Trumpinys" required>
        <Input id="shortname" v-model="form.shortname" />
      </FormFieldWrapper>
      <FormFieldWrapper id="type" label="Tipas" required>
        <Select v-model="form.type">
          <SelectTrigger>
            <SelectValue placeholder="Pasirinkite tipą..." />
          </SelectTrigger>
          <SelectContent>
            <SelectItem v-for="option in typeOptions" :key="option.value" :value="option.value">
              {{ option.label }}
            </SelectItem>
          </SelectContent>
        </Select>
      </FormFieldWrapper>
      <FormFieldWrapper id="alias" label="Alias">
        <Input id="alias" v-model="form.alias" />
      </FormFieldWrapper>
      <FormFieldWrapper id="shortname_vu" label="Trumpinys VU">
        <Input id="shortname_vu" v-model="form.shortname_vu" />
      </FormFieldWrapper>
      <FormFieldWrapper id="primary_institution_id" label="Pagrindinė įstaiga">
        <Select v-model="primaryInstitutionIdString">
          <SelectTrigger>
            <SelectValue placeholder="Pasirinkite įstaigą..." />
          </SelectTrigger>
          <SelectContent>
            <SelectItem v-for="option in institutionOptions" :key="option.value" :value="String(option.value)">
              {{ option.label }}
            </SelectItem>
          </SelectContent>
        </Select>
      </FormFieldWrapper>
    </FormElement>
  </AdminForm>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { useForm } from "@inertiajs/vue3";

import { Input } from "@/Components/ui/input";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/Components/ui/select";
import FormElement from "./FormElement.vue";
import FormFieldWrapper from "./FormFieldWrapper.vue";
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

// Shadcn Select requires string values
const primaryInstitutionIdString = computed({
  get: () => form.primary_institution_id != null ? String(form.primary_institution_id) : '',
  set: (val: string) => { form.primary_institution_id = val ? Number(val) : null; },
});
</script>
