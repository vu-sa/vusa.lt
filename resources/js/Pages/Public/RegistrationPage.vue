<template>
  <article class="gap-y-4 pt-8 last:pb-2">
    <h1>
      {{ $t(form.name) }}
    </h1>
    <div class="typography">
      <div v-html="form.description" />
    </div>
    <div class="mt-8 max-w-prose text-base">
      <AutoForm :form="autoform" class="space-y-6" :schema="formSchema" :field-config="formFieldConfig"
        @submit="onSubmit">
        <NButton attr-type="submit" type="primary">
          {{ $t("Pateikti") }}
        </NButton>
      </AutoForm>
    </div>
  </article>
</template>

<script setup lang="ts">
import { trans as $t } from "laravel-vue-i18n";
import { z } from "zod";
import { useForm } from 'vee-validate'
import AutoForm from "@/Components/ShadcnVue/ui/auto-form/AutoForm.vue";
import { router, usePage } from "@inertiajs/vue3";
import { toTypedSchema } from "@vee-validate/zod";
import { ref, watch } from "vue";

const { form } = defineProps<{
  form: unknown;
}>();

// dynamically build form schema from form.formFields
const schema = {}

const removeInertiaBeforeEventListener = ref<VoidFunction | null>(null);

const checkIfFieldIsLocalized = (field: Record<string, any>) => {
  return field.options?.[0].label?.lt || field.options?.[0].label?.en;
}

form.form_fields.forEach((field: Record<string, any>) => {
  let fieldSchema: z.ZodType;
  let options: string[] = [];

  switch (field.type) {
    case 'string':
      fieldSchema = z.string();
      break;
    case 'number':
      fieldSchema = z.number();
      break;
    case 'enum':
      // check if options are really localized, contains lt or en
      if (checkIfFieldIsLocalized(field)) {
        options = field.options.map((option: Record<string, any>) => String(option.label[usePage().props.app.locale]))
      } else {
        options = field.options?.map((option: Record<string, any>) => String(option.label))
      }

      fieldSchema = z.enum(options);
      break;
    case 'date':
      fieldSchema = z.coerce.date();
      break;
    case 'boolean':
      fieldSchema = z.boolean();

      if (field.is_required) {
        fieldSchema = fieldSchema.refine(value => value, {
          message: 'You must accept to proceed.',
        })
      }
  }

  if (field.subtype === 'email') {
    fieldSchema = fieldSchema.email();
  }

  if (!field.is_required) {
    fieldSchema = fieldSchema.optional();
  }

  if (field.default_value) {
    fieldSchema = fieldSchema.default(field.default_value);
  }

  schema['form-field-' + field.id] = fieldSchema;
});

const formSchema = z.object(schema);

const formFieldConfig = form.form_fields.reduce((acc, field: Record<string, any>) => {
  acc['form-field-' + field.id] = {
    description: field.description,
    label: field.label,
  };

  if (field.subtype === 'textarea') {
    acc['form-field-' + field.id].component = 'textarea';
  }

  return acc;
}, {});

const autoform = useForm({
  validationSchema: toTypedSchema(formSchema),
})

watch(() => autoform.meta.value.dirty, () => {
  const message = 'You have unsaved changes. Continue?';

  removeInertiaBeforeEventListener.value = router.on('before', (event) => {
    if (!confirm(message)) {
      event.preventDefault();
    }
  });
  window.addEventListener('beforeunload', (event) => {
    if (!confirm(message)) {
      event.preventDefault();
    }
  });
}, { once: true });

const onSubmit = (data: Record<string, any>) => {

  // skip onbefore event
  if (removeInertiaBeforeEventListener.value) removeInertiaBeforeEventListener.value?.();

  // transform data to match the backend
  // remove form-field- prefix

  const transformedData = Object.keys(data).reduce((acc, key) => {
    let fieldData = data[key];

    const formField = form.form_fields.find((field: Record<string, any>) => String(field.id) === key.replace('form-field-', ''));

    if (formField?.type === 'enum') {
      // get value, from selected label

      if (checkIfFieldIsLocalized(formField)) {
        const selectedOption = formField.options.find((option: Record<string, any>) => option.label[usePage().props.app.locale] === fieldData);

        if (selectedOption) {
          fieldData = selectedOption.value;
        }
      } else {
        const selectedOption = formField.options.find((option: Record<string, any>) => option.label === fieldData);

        if (selectedOption) {
          fieldData = selectedOption.value;
        }
      }
    }

    const fieldId = key.replace('form-field-', '');

    acc[fieldId] = {
      value: fieldData,
    };

    return acc;
  }, {});

  router.post(route('registrations.store', { form: form.id }), { data: transformedData });
};

</script>
