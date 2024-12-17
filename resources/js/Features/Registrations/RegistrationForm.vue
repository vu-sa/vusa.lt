<template>
  <AutoForm :form="autoform" class="space-y-6" :schema="formSchema" :field-config="formFieldConfig" @submit="onSubmit">
    <NButton attr-type="submit" type="primary">
      {{ $t("Pateikti") }}
    </NButton>
  </AutoForm>
</template>

<script setup lang="ts">
import { z } from "zod";
import AutoForm from "@/Components/ShadcnVue/ui/auto-form/AutoForm.vue";
import { useForm } from 'vee-validate'
import { toTypedSchema } from "@vee-validate/zod";
import { ref, watch } from "vue";
import { router } from "@inertiajs/vue3";

const { form } = defineProps<{
  form: Record<string, any>;
}>()

const emit = defineEmits<{
  (e: "submit", data: Record<string, any>): void;
}>()

const schema = {}

const removeInertiaBeforeEventListener = ref<VoidFunction | null>(null);

const checkIfFieldIsLocalized = (field: Record<string, any>) => {
  return field.options?.[0].label?.lt || field.options?.[0].label?.en;
}

// dynamically build form schema from form.formFields
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

  const description = Array.isArray(field.description) ? "" : field.description

  acc['form-field-' + field.id] = {
    description,
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

  emit('submit', transformedData);
};
</script>
