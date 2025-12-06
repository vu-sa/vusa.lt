<template>
  <PageContent title="Posėdžių rodymo nustatymai" :back-url="route('dashboard')">
    <UpsertModelLayout>
      <AdminForm :model="form" @submit:form="handleFormSubmit">
        <FormElement>
          <template #title>
            Institucijų tipai su viešais posėdžiais
          </template>
          <template #description>
            Pasirinkite, kurių institucijų tipų posėdžiai bus rodomi viešai kontaktų puslapiuose.
            Pavyzdžiui: studijų kolegija, KAP taryba, studijų programų komitetas.
          </template>
          <NFormItem>
            <template #label>
              <span class="inline-flex items-center gap-1">
                <NIcon :component="Icons.TYPE" />
                Institucijų tipai
              </span>
            </template>
            <NSelect
              v-model:value="form.type_ids"
              multiple
              filterable
              :options="available_types"
              label-field="title"
              value-field="id"
              placeholder="Pasirinkti institucijų tipus"
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
import Icons from "@/Types/Icons/regular";
import { NFormItem } from "naive-ui";

const props = defineProps<{
  selected_type_ids: number[];
  available_types: App.Entities.Type[];
}>();

const form = useForm({
  type_ids: props.selected_type_ids
});

const handleFormSubmit = () => {
  form.post(route("settings.meetings.update"));
};
</script>
