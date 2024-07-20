<template>
  <NForm :model="form" label-placement="top">
    <div class="flex flex-col">
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
          <NSelect v-model:value="form.primary_institution_id" :options="institutionOptions" clearable />
        </NFormItem>
      </FormElement>
    </div>
    <div class="flex justify-end gap-2">
      <UpsertModelButton :form="form" :model-route="modelRoute">
        Sukurti
      </UpsertModelButton>
    </div>
  </NForm>
</template>

<script setup lang="tsx">
import { useForm } from "@inertiajs/vue3";

import FormElement from "./FormElement.vue";
import UpsertModelButton from "../Buttons/UpsertModelButton.vue";

const props = defineProps<{
  tenant: App.Entities.Tenant;
  assignableInstitutions: Array<App.Entities.Institution>;
  modelRoute: string;
}>();

const form = useForm("tenant", props.tenant);

const typeOptions = [
  { label: "PKP", value: "pkp" },
  { label: "Padalinys", value: "padalinys" },
  { label: "Pagrindinis", value: "pagrindinis" },
];

const institutionOptions = props.assignableInstitutions.map((institution) => ({
  label: institution.name,
  value: institution.id,
}));
</script>
