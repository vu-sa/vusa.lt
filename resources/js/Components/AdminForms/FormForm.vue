<template>
  <AdminForm :model="form" label-placement="top" @submit:form="$emit('submit:form', form)" @delete="$emit('delete')">
    <FormElement>
      <template #title>
        {{ $t("forms.context.main_info") }}
      </template>
      <template #description>
        {{ $t("forms.helpers.form_main_info") }}
      </template>
      <FormFieldWrapper id="name" :label="$t('forms.fields.name')" required>
        <MultiLocaleInput v-model:input="form.name" />
      </FormFieldWrapper>

      <div class="space-y-2">
        <Label class="inline-flex items-center gap-2">
          {{ $t("forms.fields.description") }}
          <SimpleLocaleButton v-model:locale="locale" />
        </Label>
        <TiptapEditor v-if="locale === 'lt'" v-model="form.description.lt" preset="full" :html="true" />
        <TiptapEditor v-else v-model="form.description.en" preset="full" :html="true" />
      </div>

      <div class="space-y-3">
        <PermalinkField
          :permalink="form.path.lt" :base-url="registrationBaseUrl('lt')" label="LT"
          :view-url="publicUrl('lt')"
          @update:permalink="onPathInput('lt', $event)" />
        <PermalinkField
          :permalink="form.path.en" :base-url="registrationBaseUrl('en')" label="EN"
          :view-url="publicUrl('en')"
          @update:permalink="onPathInput('en', $event)" />
        <p v-if="pathChangedOnExistingForm" class="flex items-center gap-1 text-xs text-amber-600 dark:text-amber-500">
          <IFluentWarning24Regular class="h-3.5 w-3.5 shrink-0" />
          {{ $t('Atsargiai: pakeitus nuorodą, sena nuoroda nebeveiks!') }}
        </p>
      </div>

      <FormFieldWrapper v-if="assignableTenants && assignableTenants.length > 0" id="tenant_id" :label="$t('forms.fields.tenant')">
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

      <FormFieldWrapper id="publish_time" :label="$t('forms.fields.form_publish_time')">
        <DateTimePicker v-model="publishTimeDate" :placeholder="$t('forms.placeholders.date')" @change="onPublishTimeChange" />
      </FormFieldWrapper>
    </FormElement>

    <FormElement>
      <template #title>
        {{ $t("forms.sections.form_fields") }}
      </template>
      <template #description>
        <p>{{ $t("forms.helpers.registrations_count", { count: form.registrations_count ?? 0 }) }}</p>
        <p v-if="form.registrations_count > 0">
          <Link :href="route('forms.show', form.id)">
            {{ $t("forms.helpers.view_registrations") }}
          </Link>
        </p>
      </template>
      <p v-if="hasRegistrations" class="flex items-center gap-1 text-xs text-muted-foreground">
        <IFluentInfo16Regular class="h-3.5 w-3.5 shrink-0" />
        {{ $t('Formos laukelių pridėti ar ištrinti nebegalima, nes forma jau turi registracijų.') }}
      </p>
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
    <CardModal v-model:show="showFormFieldModal" :title="$t('forms.sections.form_field')" @close="showFormFieldModal = false">
      <FormFieldForm :field-models="fieldModelOptions" :field-model-attributes="fieldModelFields" :has-registrations
        :form-field="selectedFormField" @submit="handleFormFieldSubmitted" />
    </CardModal>
  </AdminForm>
</template>

<script setup lang="ts">
import { Link, usePage, useForm } from '@inertiajs/vue3';
import { computed, ref, toRaw, watch } from 'vue';

import MultiLocaleInput from '../FormItems/MultiLocaleInput.vue';
import SimpleLocaleButton from '../Buttons/SimpleLocaleButton.vue';
import TiptapEditor from '../TipTap/TiptapEditor.vue';
import SortableFormFieldsTable from '../Tables/SortableFormFieldsTable.vue';
import CardModal from '../Dialogs/CardModal.vue';

import AdminForm from './AdminForm.vue';
import FormFieldWrapper from './FormFieldWrapper.vue';
import FormElement from './FormElement.vue';
import FormFieldForm from './FormFieldForm.vue';
import PermalinkField from './PermalinkField.vue';

import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Label } from '@/Components/ui/label';
import { DateTimePicker } from '@/Components/ui/date-picker';
import { Button } from '@/Components/ui/button';
import { formFieldTemplate } from '@/Types/formTemplates';
import { generateSlug } from '@/Utils/String';

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

// Translatable columns come back as null when never filled in, but the editor binds
// straight into .lt / .en, so normalise them up front.
const form = useForm({
  ...props.form,
  description: props.form.description ?? { lt: '', en: '' },
  path: props.form.path ?? { lt: '', en: '' },
});

const hasRegistrations = computed(() => form?.registrations_count > 0);

type Locale = 'lt' | 'en';

const originalPath: Record<Locale, string> = {
  lt: props.form.path?.lt ?? '',
  en: props.form.path?.en ?? '',
};

// A path that already has a value was authored deliberately — never regenerate over it.
const pathTouched = ref<Record<Locale, boolean>>({
  lt: originalPath.lt !== '',
  en: originalPath.en !== '',
});

const pathChangedOnExistingForm = computed(() =>
  (originalPath.lt !== '' && form.path.lt !== originalPath.lt)
  || (originalPath.en !== '' && form.path.en !== originalPath.en),
);

const registrationSegment = (locale: Locale) => (locale === 'lt' ? 'registracija' : 'registration');

const registrationBaseUrl = (locale: Locale) =>
  `${usePage().props.app.url}/${locale}/${registrationSegment(locale)}`;

const publicUrl = (locale: Locale) =>
  form.path[locale] ? `${registrationBaseUrl(locale)}/${form.path[locale]}` : undefined;

const onPathInput = (locale: Locale, value: string) => {
  pathTouched.value[locale] = true;
  form.path[locale] = value;
};

// Keep the URL in step with the name until the user takes the field over.
(['lt', 'en'] as Locale[]).forEach((locale) => {
  watch(() => form.name?.[locale], (name) => {
    if (pathTouched.value[locale]) {
      return;
    }

    form.path[locale] = generateSlug(String(name || ''));
  });
});

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
  // Clone: formFieldTemplate is a shared module-level object, mutating it would leak
  // the previous field's values into every subsequent one.
  selectedFormField.value = structuredClone(formFieldTemplate);
  // NOTE: the backend treats a 'new-' prefixed id as a field that isn't persisted yet.
  selectedFormField.value.id = `new-${crypto.randomUUID()}`;
  selectedFormField.value.order = form.form_fields.length + 1;
  showFormFieldModal.value = true;
}

function handleEditFormField(model) {
  // Clone so dismissing the modal discards the edits instead of applying them.
  selectedFormField.value = structuredClone(toRaw(model));
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
