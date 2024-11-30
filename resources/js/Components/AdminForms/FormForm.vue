<template>
  <AdminForm :model="form" label-placement="top" @submit:form="$emit('submit:form', form)" @delete="$emit('delete')">
    <FormElement>
      <template #title>
        {{ $t("forms.context.main_info") }}
      </template>
      <template #description>
        Pagrindinė informacija apie registracijos formą.
      </template>
      <NFormItem required>
        <template #label>
          <span class="inline-flex items-center gap-1">
            <NIcon :component="Icons.TITLE" />
            Pavadinimas
          </span>
        </template>
        <MultiLocaleInput v-model:input="form.name" />
      </NFormItem>

      <NFormItem>
        <template #label>
          <div class="inline-flex items-center gap-2">
            Aprašymas
            <SimpleLocaleButton v-model:locale="locale" />
          </div>
        </template>
        <TipTap v-if="locale === 'lt'" v-model="form.description.lt" html />
        <TipTap v-else v-model="form.description.en" html />
      </NFormItem>
      <NFormItem>
        <template #label>
          <span class="inline-flex items-center gap-1">
            Nuoroda
          </span>
        </template>
        <MultiLocaleInput v-model:input="form.path" />
      </NFormItem>
      <NFormItem label="Padalinys">
        <NSelect v-model:value="form.tenant_id" :options="assignableTenants" label-field="shortname" value-field="id"
          placeholder="VU SA ..." :default-value="assignableTenants[0].id ?? ''" />
      </NFormItem>
    </FormElement>

    <FormElement>
      <template #title>
        Formos elementai
      </template>
      <SortableFormFieldsTable v-model="form.form_fields" class="mt-2">
        <template #default="{ model }">
          <div class="grid grid-cols-[20px_,22px,_1fr,_80px] items-center gap-1 pr-2 text-zinc-700 dark:text-zinc-200">
            <div class="flex flex-row items-center gap-2 pr-1.5">
              <IFluentTextT24Regular v-if="model.type === 'string'" />
              <IFluentCheckboxChecked24Regular v-if="model.type === 'boolean'" />
              <IFluentTextBulletListLtr24Filled v-if="model.type === 'enum'" />
              <IFluentNumberSymbol24Filled v-if="model.type === 'number'" />
              <IFluentCalendarLtr24Regular v-if="model.type === 'date'" />
            </div>
            <div class="flex flex-row pr-3">
              <IFluentTextAsterisk20Filled v-if="model.is_required" color="red" />
            </div>
            <span class="my-1">{{ model.label?.lt }}</span>
            <div class="flex justify-end gap-2">
              <NButton size="tiny" @click="handleEditFormField(model)">
                <template #icon>
                  <IFluentEdit24Filled />
                </template>
              </NButton>
              <NButton size="tiny" @click="handleDeleteFormField(model)">
                <template #icon>
                  <IFluentDelete24Filled color="red" />
                </template>
              </NButton>
            </div>
          </div>
        </template>
      </SortableFormFieldsTable>
      <div class="mt-4">
        <NButton type="primary" @click="handleNewFormFieldCreate">
          <template #icon>
            <IFluentAdd24Filled />
          </template>
          {{ $t("forms.add") }}
        </NButton>
      </div>
    </FormElement>
    <CardModal v-model:show="showFormFieldModal" title="Formos laukelis" @close="showFormFieldModal = false">
      <FormFieldForm :form-field="selectedFormField" @submit="handleFormFieldSubmitted" />
    </CardModal>
  </AdminForm>
</template>

<script setup lang="ts">
import { useForm } from "@inertiajs/vue3";
import { ref } from "vue";

import { formFieldTemplate } from "@/Types/formTemplates";
import FormElement from "./FormElement.vue";
import Icons from "@/Types/Icons/filled";
import MultiLocaleInput from "../FormItems/MultiLocaleInput.vue";
import SimpleLocaleButton from "../Buttons/SimpleLocaleButton.vue";
import TipTap from "../TipTap/OriginalTipTap.vue";
import AdminForm from "./AdminForm.vue";
import SortableFormFieldsTable from "../Tables/SortableFormFieldsTable.vue";
import CardModal from "../Modals/CardModal.vue";
import FormFieldForm from "./FormFieldForm.vue";

defineEmits<{
  (event: "submit:form", form: unknown): void;
  (event: "delete"): void;
}>();

const props = defineProps<{
  form: any;
  assignableTenants: any[];
}>();

const locale = ref("lt");

const showFormFieldModal = ref(false);
const selectedFormField = ref(formFieldTemplate);

const form = useForm("registrationForm", props.form);

function handleNewFormFieldCreate() {
  selectedFormField.value = formFieldTemplate;
  // add string id to the form field
  // NOTE: in backend, the string length is checked to know if the ID is generated or not
  selectedFormField.value.id = Math.random().toString(36).substring(7);
  selectedFormField.value.order = form.form_fields.length + 1;
  showFormFieldModal.value = true;
}

function handleEditFormField(model) {
  selectedFormField.value = model;
  showFormFieldModal.value = true;
}

function handleDeleteFormField(model) {
  // Find ID of the form field
  const formFieldIndex = form.form_fields.findIndex((field) => field.id === model.id);
  if (formFieldIndex !== -1) {
    form.form_fields.splice(formFieldIndex, 1);
  }
}

function handleFormFieldSubmitted(formField: any) {
  // Find ID of the form field
  const formFieldIndex = form.form_fields.findIndex((field) => field.id === formField.id);

  if (formFieldIndex !== -1) {
    form.form_fields[formFieldIndex] = formField;
  } else {
    form.form_fields.push(formField);
  }

  showFormFieldModal.value = false;
}

</script>
