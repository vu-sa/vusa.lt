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

form.form_fields.forEach((field: Record<string, any>) => {
  let fieldSchema: any;

  switch (field.type) {
    case 'string':
      fieldSchema = z.string();
      break;
    case 'number':
      fieldSchema = z.number();
      break;
    case 'enum':
      const options = field.options.map((option: Record<string, any>) => String(option.label[usePage().props.app.locale]))

      fieldSchema = z.enum(options);
      break;
    case 'date':
      fieldSchema = z.coerce.date();
      break;
    case 'boolean':
      fieldSchema = z.boolean();
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

  schema['form-field-' + field.id] = fieldSchema.describe(field.label);
});

const formSchema = z.object(schema);

const formFieldConfig = form.form_fields.reduce((acc, field: Record<string, any>) => {
  acc['form-field-' + field.id] = {
    description: field.description,
  };

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
    const fieldId = key.replace('form-field-', '');
    acc[fieldId] = data[key];
    return acc;
  }, {});

  router.post(route('registrations.store', { form: form.id }), { data: transformedData });
};

</script>
