<template>
  <NForm ref="form" :model>
    <NFormItem label="Pavadinimas" path="label" required>
      <MultiLocaleInput v-model:input="model.label" />
    </NFormItem>
    <NFormItem label="Trumpas paaiškinimas" path="description">
      <MultiLocaleInput v-model:input="model.description" />
    </NFormItem>
    <NFormItem label="Tipas" path="type" required>
      <NSelect v-model:value="model.type" :disabled="hasRegistrations" :options />
    </NFormItem>
    <NFormItem v-if="subtypeOptions.length > 0" label="Subtipas" path="subtype">
      <NSelect v-model:value="model.subtype" clearable :disabled="hasRegistrations" :options="subtypeOptions" />
    </NFormItem>
    <template v-if="model.type === 'enum'">
      <NFormItem v-if="!model.use_model_options" label="Reikšmės" path="options">
        <NDynamicInput v-model:value="model.options" :disabled="hasRegistrations" @create="onCreate">
          <template #default="{ value }">
            <div class="mt-4 flex flex-row items-center gap-2">
              <NFormItem :show-feedback="false" label="Reikšmė" path="value" required class="self-start">
                <NInput v-model:value="value.value" :disabled="hasRegistrations" />
              </NFormItem>
              <NFormItem :disabled="hasRegistrations" class="pb-4" :show-feedback="false" label="Pavadinimas"
                path="label" required>
                <MultiLocaleInput v-model:input="value.label" />
              </NFormItem>
            </div>
          </template>
        </NDynamicInput>
      </NFormItem>
      <NCollapse class="mb-6">
        <NCollapseItem title="Advanced" name="1">
          <NFormItem label="Naudoti iš duombazės?" path="use_model_options" required>
            <NSwitch v-model:value="model.use_model_options" />
          </NFormItem>
          <NFormItem label="Modelio pavadinimas" path="model_name">
            <NSelect v-model:value="model.options_model" :disabled="!model.use_model_options" :options="fieldModels" />
          </NFormItem>
          <NFormItem label="Modelio laukas" path="model_field">
            <NSelect v-model:value="model.options_model_field" :disabled="!model.use_model_options"
              :options="fieldModelAttributes" />
          </NFormItem>
        </NCollapseItem>
      </NCollapse>
    </template>
    <NFormItem label="Ar būtinas?" path="is_required" required>
      <NSwitch v-model:value="model.is_required" />
    </NFormItem>
    <NFormItem label="Automatiškai įrašomas atsakymas" path="default_value">
      <MultiLocaleInput v-model:input="model.default_value" />
    </NFormItem>
    <NFormItem label="Pagalbinis tekstas laukelyje" path="placeholder">
      <MultiLocaleInput v-model:input="model.placeholder" />
    </NFormItem>
    <NFormItem :show-label="false">
      <NButton type="primary" @click="handleSubmit">
        Pateikti
      </NButton>
    </NFormItem>
  </NForm>
</template>

<script setup lang="ts">
import {
  type FormInst,
  NButton,
  NForm,
} from "naive-ui";
import { computed, ref } from "vue";
import { useForm } from "@inertiajs/vue3";
import MultiLocaleInput from "../FormItems/MultiLocaleInput.vue";

// import { modelDefaults } from "@/Types/formOptions";

const emit = defineEmits<{
  (e: "submit", form: any): void;
}>();

const props = defineProps<{
  formField: App.Entities.FormField | Record<string, any>;
  hasRegistrations?: boolean;
  fieldModels?: { value: string; label: string }[];
  fieldModelAttributes?: { value: string; label: string }[];
}>();

const form = ref<FormInst | null>(null);
const model = useForm(props.formField);

const options = [
  {
    value: "string",
    label: "Tekstas",
  },
  {
    value: "boolean",
    label: "Taip / Ne",
  },
  {
    value: "enum",
    label: "Pasirinkimas",
  },
  {
    value: "number",
    label: "Skaičius",
  },
  {
    value: "date",
    label: "Data",
  },
];

// NOTE: Used in mailables to know which fields to use (also in validation sometimes)
const subtypeOptions = computed(() => {
  if (model.type === "string") {
    return [
      {
        value: "name",
        label: "Vardas",
      },
      {
        value: "email",
        label: "El. paštas",
      },
      {
        value: "textarea",
        label: "Ilgo teksto laukas",
      },
    ];
  }
  return [];
});

function onCreate() {
  return {
    value: "",
    label: { lt: "", en: "" },
  };
}

const handleSubmit = () => {
  // validate form
  form.value?.validate((errors) => {
    if (!errors) {
      emit("submit", model.data());
    }
  });
};
</script>

<style>
.n-dynamic-input .n-dynamic-input-item .n-dynamic-input-item__action {
  align-self: center !important;
}
</style>
