<template>
  <AutoForm :form="autoform" class="space-y-6" :schema="formSchema" :field-config="formFieldConfig" @submit="onSubmit">
    <template v-for="field in fieldsWithDescription" #[`form-field-${field.id}`]="slotProps" :key="field.id">
      <div class="mb-6">
        <AutoFormField v-bind="slotProps" />
        <div class="text-sm mt-1 text-zinc-600 dark:text-zinc-400" v-html="field.description" />
      </div>
    </template>
    <!-- Hide prefilled fields -->
    <template v-for="fieldId in hiddenFieldIds" #[`form-field-${fieldId}`]="slotProps" :key="'hidden-' + fieldId">
      <div class="hidden">
        <AutoFormField v-bind="slotProps" />
      </div>
    </template>
    <Button type="submit">
      {{ $t("Pateikti") }}
    </Button>
  </AutoForm>
</template>

<script setup lang="ts">
import { z } from 'zod';
import { useForm } from 'vee-validate';
import { toTypedSchema } from '@vee-validate/zod';
import { ref, watch, computed, onMounted } from 'vue';
import { router, usePage } from '@inertiajs/vue3';

import { Button } from '@/Components/ui/button';
import AutoForm from '@/Components/ui/auto-form/AutoForm.vue';
import AutoFormField from '@/Components/ui/auto-form/AutoFormField.vue';

interface PrefilledValue {
  value: string | number | boolean;
  hidden?: boolean;
}

const props = defineProps<{
  form: Record<string, any>;
  prefilledValues?: Record<string, PrefilledValue>;
}>();

const emit = defineEmits<(e: 'submit', data: Record<string, any>) => void>();

const schema = {};
const fieldsWithDescription = ref([]);

const removeInertiaBeforeEventListener = ref<VoidFunction | null>(null);

// Track which fields should be hidden (prefilled with hidden: true)
const hiddenFieldIds = computed(() => {
  if (!props.prefilledValues) return [];
  return Object.entries(props.prefilledValues)
    .filter(([_, config]) => config.hidden)
    .map(([fieldId]) => fieldId);
});

const checkIfFieldIsLocalized = (field: Record<string, any>) => {
  return field.options?.[0].label?.lt || field.options?.[0].label?.en;
};

const getEnumLabel = (field: Record<string, any>, value: string | number | boolean) => {
  if (field.type === 'enum' && field.options) {
    const option = field.options.find((opt: { value: string | number | boolean }) => {
      return String(opt.value) === String(value);
    });

    if (option) {
      if (checkIfFieldIsLocalized(field)) {
        const page = usePage();
        const locale = (page.props as any).app?.locale ?? 'lt';

        return option.label[locale] ?? option.label;
      }

      return option.label;
    }
  }

  return value;
};

// Get default value for a field (checking prefilled values first)
const getDefaultValue = (field: Record<string, any>) => {
  const prefilledConfig = props.prefilledValues?.[field.id];

  if (prefilledConfig) {
    // For enum fields, convert the raw value to its label; otherwise returns the value as-is
    return getEnumLabel(field, prefilledConfig.value);
  }
  return field.default_value;
};

// dynamically build form schema from form.formFields
props.form.form_fields.forEach((field: Record<string, any>) => {
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
        options = field.options.map((option: Record<string, any>) => String(option.label[usePage().props.app.locale]));
      }
      else {
        options = field.options?.map((option: Record<string, any>) => String(option.label));
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
        });
      }
  }

  if (field.subtype === 'email') {
    fieldSchema = fieldSchema.email();
  }

  if (!field.is_required) {
    fieldSchema = fieldSchema.optional();
  }

  const defaultValue = getDefaultValue(field);
  if (defaultValue) {
    fieldSchema = fieldSchema.default(defaultValue);
  }

  if (field.description) {
    fieldsWithDescription.value.push({
      id: field.id,
      description: field.description,
    });
  }

  schema[`form-field-${field.id}`] = fieldSchema;
});

const formSchema = z.object(schema);

const formFieldConfig = props.form.form_fields.reduce((acc, field: Record<string, any>) => {
  const description = Array.isArray(field.description) ? '' : field.description;

  acc[`form-field-${field.id}`] = {
    label: field.label,
  };

  if (field.subtype === 'textarea') {
    acc[`form-field-${field.id}`].component = 'textarea';
  }

  return acc;
}, {});

const autoform = useForm({
  validationSchema: toTypedSchema(formSchema),
});

// Set initial values for prefilled fields after mount
onMounted(() => {
  if (props.prefilledValues) {
    const initialValues: Record<string, any> = {};

    for (const [fieldId, config] of Object.entries(props.prefilledValues)) {
      const field = props.form.form_fields.find((f: Record<string, any>) => String(f.id) === String(fieldId));
      if (field) {
        const formFieldKey = `form-field-${fieldId}`;

        // For enum fields, convert value to label
        if (field.type === 'enum' && field.options) {
          const option = field.options.find((opt: { value: string | number }) =>
            String(opt.value) === String(config.value),
          );
          if (option) {
            if (checkIfFieldIsLocalized(field)) {
              initialValues[formFieldKey] = option.label[usePage().props.app.locale];
            }
            else {
              initialValues[formFieldKey] = option.label;
            }
          }
        }
        else {
          initialValues[formFieldKey] = config.value;
        }
      }
    }

    if (Object.keys(initialValues).length > 0) {
      // Use resetForm to set values without triggering validation
      autoform.resetForm({
        values: { ...autoform.values, ...initialValues },
      });
    }
  }
});

// Track if we're submitting to avoid showing dialog
const isSubmitting = ref(false);

watch(() => autoform.meta.value.dirty, (isDirty) => {
  if (isDirty) {
    // Add Inertia listener for SPA navigation
    removeInertiaBeforeEventListener.value = router.on('before', (event) => {
      // Skip for prefetch requests
      if (event.detail?.visit?.prefetch) {
        return;
      }

      // Skip if we're submitting
      if (isSubmitting.value) {
        return;
      }

      if (!confirm('You have unsaved changes. Continue?')) {
        event.preventDefault();
      }
    });
  }
}, { once: true });

const onSubmit = (data: Record<string, any>) => {
  isSubmitting.value = true;

  // skip onbefore event
  if (removeInertiaBeforeEventListener.value) removeInertiaBeforeEventListener.value?.();

  // transform data to match the backend
  // remove form-field- prefix

  const transformedData = Object.keys(data).reduce((acc, key) => {
    let fieldData = data[key];

    const formField = props.form.form_fields.find((field: Record<string, any>) => String(field.id) === key.replace('form-field-', ''));

    if (formField?.type === 'enum') {
      // get value, from selected label

      if (checkIfFieldIsLocalized(formField)) {
        const selectedOption = formField.options.find((option: Record<string, any>) => option.label[usePage().props.app.locale] === fieldData);

        if (selectedOption) {
          fieldData = selectedOption.value;
        }
      }
      else {
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

  autoform.resetForm();
};
</script>
