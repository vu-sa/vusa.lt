<template>
  <article class="typography gap-y-4 pt-8 last:pb-2">
    <h1>
      {{ $t(form.name) }}
    </h1>
    <div v-html="form.description" />
    <div class="max-w-prose space-y-2 text-base">
      <AutoForm class="space-y-6" :schema="formSchema" :field-config="formFieldConfig" @submit="onSubmit">
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
import AutoForm from "@/Components/ShadcnVue/ui/auto-form/AutoForm.vue";
//import AutoFormField from "@/Components/ShadcnVue/ui/auto-form/AutoFormField.vue";
import { usePage } from "@inertiajs/vue3";

const { form } = defineProps<{
  form: unknown;
}>();

// dynamically build form schema from form.formFields
const schema = {}

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

      console.log(options);

      fieldSchema = z.enum(options);
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

  schema[field.label] = fieldSchema;
});

const formSchema = z.object(schema);

const formFieldConfig = form.form_fields.reduce((acc, field: Record<string, any>) => {
  acc[field.label] = {
    description: field.description,
  };

  return acc;
}, {});

const onSubmit = (data: Record<string, any>) => {
  console.log(data);
};
</script>
