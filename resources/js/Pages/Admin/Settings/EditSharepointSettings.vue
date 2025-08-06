<template>
  <PageContent title="SharePoint nustatymai" :back-url="'/'">
    <UpsertModelLayout>
      <AdminForm :model="form" @submit:form="handleFormSubmit">
        <FormElement>
          <template #title>
            Leidimų nustatymai
          </template>
          <template #description>
            Numatyti leidimų galiojimo laiko nustatymai dokumentams.
          </template>
          <NFormItem label="Leidimų galiojimo laikas (dienos)">
            <NInputNumber 
              v-model:value="form.permission_expiry_days"
              :min="1"
              :max="3650"
              placeholder="365"
            />
          </NFormItem>
        </FormElement>

        <FormElement>
          <template #title>
            Failų organizavimas
          </template>
          <template #description>
            Numatytoji failų struktūra SharePoint sistemoje. Galite naudoti kintamuosius: {type}, {name}, {tenant}.
          </template>
          <NFormItem label="Numatytoji aplankų struktūra">
            <NInput 
              v-model:value="form.default_folder_structure" 
              placeholder="General/{type}/{name}"
              type="text"
            />
          </NFormItem>
        </FormElement>
      </AdminForm>
    </UpsertModelLayout>
  </PageContent>
</template>

<script setup lang="ts">
import { useForm } from "@inertiajs/vue3";

import PageContent from "@/Components/Layouts/AdminContentPage.vue";
import UpsertModelLayout from "@/Components/Layouts/FormUpsertLayout.vue";
import AdminForm from "@/Components/AdminForms/AdminForm.vue";
import FormElement from "@/Components/AdminForms/FormElement.vue";
import { NFormItem, NInput, NInputNumber } from "naive-ui";

const { 
  permission_expiry_days,
  default_folder_structure
} = defineProps<{
  permission_expiry_days: number;
  default_folder_structure: string;
}>();

const form = useForm({ 
  permission_expiry_days,
  default_folder_structure
});

const handleFormSubmit = () => {
  form.post(route("sharepoint.settings.update"));
};
</script>