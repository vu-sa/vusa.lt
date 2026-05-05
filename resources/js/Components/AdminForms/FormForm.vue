<template>
  <AdminForm :model="form" label-placement="top" @submit:form="$emit('submit:form', form)" @delete="$emit('delete')">
    <FormElement>
      <template #title>
        {{ $t("forms.context.main_info") }}
      </template>
      <template #description>
        Pagrindinė informacija apie registracijos formą.
      </template>
      <FormFieldWrapper id="name" label="Pavadinimas" required>
        <MultiLocaleInput v-model:input="form.name" />
      </FormFieldWrapper>

      <div class="space-y-2">
        <Label class="inline-flex items-center gap-2">
          Aprašymas
          <SimpleLocaleButton v-model:locale="locale" />
        </Label>
        <TiptapEditor v-if="locale === 'lt'" v-model="form.description.lt" preset="full" :html="true" />
        <TiptapEditor v-else v-model="form.description.en" preset="full" :html="true" />
      </div>

      <FormFieldWrapper id="path" label="Nuoroda">
        <MultiLocaleInput v-model:input="form.path" />
      </FormFieldWrapper>

      <FormFieldWrapper v-if="assignableTenants && assignableTenants.length > 0" id="tenant_id" label="Padalinys">
        <Select v-model="tenantIdString">
          <SelectTrigger>
            <SelectValue placeholder="VU SA ..." />
          </SelectTrigger>
          <SelectContent>
            <SelectItem v-for="tenant in assignableTenants" :key="tenant.id" :value="String(tenant.id)">
              {{ tenant.shortname }}
            </SelectItem>
          </SelectContent>
        </Select>
      </FormFieldWrapper>

      <FormFieldWrapper id="publish_time" label="Formos paskelbimo laikas">
        <DateTimePicker v-model="publishTimeDate" placeholder="Data..." @change="onPublishTimeChange" />
      </FormFieldWrapper>
    </FormElement>

    <FormElement>
      <template #title>
        Formos elementai
      </template>
      <template #description>
        <p>Registracijų skaičius: {{ form.registrations_count ?? 0 }}</p>
        <p v-if="form.registrations_count > 0">
          <Link :href="route('forms.show', form.id)">
            Peržiūrėti registracijas
          </Link>
        </p>
      </template>
      <SortableFormFieldsTable v-model="form.form_fields" class="mt-2">
        <template #default="{ model }">
          <div class="grid grid-cols-[20px__22px__1fr__80px] items-center gap-1 pr-2 text-zinc-700 dark:text-zinc-200">
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
              <Button size="icon-xs" variant="ghost" @click="handleEditFormField(model)">
                <IFluentEdit24Filled />
              </Button>
              <Button size="icon-xs" variant="ghost" :disabled="hasRegistrations" @click="handleDeleteFormField(model)">
                <IFluentDelete24Filled class="text-red-500" />
              </Button>
            </div>
          </div>
        </template>
      </SortableFormFieldsTable>
      <div class="mt-4">
        <Button :disabled="hasRegistrations" @click="handleNewFormFieldCreate">
          <IFluentAdd24Filled />
          {{ $t("forms.add") }}
        </Button>
      </div>
    </FormElement>
    <CardModal v-model:show="showFormFieldModal" title="Formos laukelis" @close="showFormFieldModal = false">
      <FormFieldForm :field-models="fieldModelOptions" :field-model-attributes="fieldModelFields" :has-registrations
        :form-field="selectedFormField" @submit="handleFormFieldSubmitted" />
    </CardModal>
  </AdminForm>
</template>

<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';

import MultiLocaleInput from '../FormItems/MultiLocaleInput.vue';
import SimpleLocaleButton from '../Buttons/SimpleLocaleButton.vue';
import TiptapEditor from '../TipTap/TiptapEditor.vue';
import SortableFormFieldsTable from '../Tables/SortableFormFieldsTable.vue';
import CardModal from '../Dialogs/CardModal.vue';

import AdminForm from './AdminForm.vue';
import FormFieldWrapper from './FormFieldWrapper.vue';
import FormElement from './FormElement.vue';
import FormFieldForm from './FormFieldForm.vue';

import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Label } from '@/Components/ui/label';
import { DateTimePicker } from '@/Components/ui/date-picker';
import { Button } from '@/Components/ui/button';
import { formFieldTemplate } from '@/Types/formTemplates';

defineEmits<{
  (event: 'submit:form', form: unknown): void;
  (event: 'delete'): void;
}>();

const props = defineProps<{
  form: any;
  assignableTenants: any[];
  fieldModelOptions?: { value: string; label: string }[];
  fieldModelFields?: { value: string; label: string }[];
}>();

const locale = ref('lt');

const showFormFieldModal = ref(false);
const selectedFormField = ref(formFieldTemplate);

// Differently from other components, we use reactive because of nested form fields
const form = reactive(props.form);

const hasRegistrations = computed(() => form?.registrations_count > 0);

// Shadcn Select requires string values
const tenantIdString = computed({
  get: () => form.tenant_id != null ? String(form.tenant_id) : '',
  set: (val: string) => { form.tenant_id = val ? Number(val) : null; },
});

// DateTimePicker works with Date objects; form.publish_time is an ISO string
const publishTimeDate = ref<Date | null>(
  form.publish_time ? new Date(form.publish_time) : null,
);

const onPublishTimeChange = (date: Date | null) => {
  form.publish_time = date ? date.toISOString() : null;
};

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
  const formFieldIndex = form.form_fields.findIndex(field => field.id === model.id);
  if (formFieldIndex !== -1) {
    form.form_fields.splice(formFieldIndex, 1);
  }
}

function handleFormFieldSubmitted(formField: any) {
  // Find ID of the form field
  const formFieldIndex = form.form_fields.findIndex(field => field.id === formField.id);

  if (formFieldIndex !== -1) {
    form.form_fields[formFieldIndex] = formField;
  }
  else {
    form.form_fields.push(formField);
  }

  showFormFieldModal.value = false;
}

</script>
