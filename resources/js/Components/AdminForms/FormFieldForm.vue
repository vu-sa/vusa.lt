<template>
  <div class="space-y-4">
    <FormFieldWrapper id="label" label="Pavadinimas" required>
      <MultiLocaleInput v-model:input="model.label" />
    </FormFieldWrapper>
    <MultiLocaleTiptapFormItem v-model:input="model.description" label="Trumpas paaiškinimas" />
    <FormFieldWrapper id="type" label="Tipas" required>
      <Select v-model="model.type" :disabled="hasRegistrations">
        <SelectTrigger>
          <SelectValue placeholder="Pasirinkite tipą" />
        </SelectTrigger>
        <SelectContent>
          <SelectItem v-for="opt in options" :key="opt.value" :value="opt.value">
            {{ opt.label }}
          </SelectItem>
        </SelectContent>
      </Select>
    </FormFieldWrapper>
    <FormFieldWrapper v-if="subtypeOptions.length > 0" id="subtype" label="Subtipas">
      <Select v-model="model.subtype" :disabled="hasRegistrations">
        <SelectTrigger>
          <SelectValue placeholder="Pasirinkite subtipą" />
        </SelectTrigger>
        <SelectContent>
          <SelectItem v-for="opt in subtypeOptions" :key="opt.value" :value="opt.value">
            {{ opt.label }}
          </SelectItem>
        </SelectContent>
      </Select>
    </FormFieldWrapper>
    <template v-if="model.type === 'enum'">
      <FormFieldWrapper v-if="!model.use_model_options" id="options" label="Reikšmės">
        <DynamicListInput v-model="model.options" :create-item="onCreate" allow-empty
          empty-text="Nėra pridėtų reikšmių" add-first-text="Pridėti pirmą reikšmę" add-text="Pridėti reikšmę">
          <template #item="{ item }">
            <div class="flex flex-row items-center gap-2">
              <FormFieldWrapper id="value" label="Reikšmė" required>
                <Input v-model="item.value" :disabled="hasRegistrations" />
              </FormFieldWrapper>
              <FormFieldWrapper id="label" label="Pavadinimas" required>
                <MultiLocaleInput v-model:input="item.label" />
              </FormFieldWrapper>
            </div>
          </template>
        </DynamicListInput>
      </FormFieldWrapper>
      <Collapsible v-model:open="advancedOpen" class="mb-6">
        <CollapsibleTrigger>
          <div
            class="flex items-center gap-2 border p-2 rounded-md cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800">
            <span>Advanced</span>
            <IFluentChevronDown24Regular v-if="!advancedOpen" />
            <IFluentChevronUp24Regular v-else />
          </div>
        </CollapsibleTrigger>
        <CollapsibleContent>
          <div class="space-y-4 p-3 pt-4">
            <FormFieldWrapper id="use_model_options" label="Naudoti iš duombazės?" required>
              <Switch v-model="model.use_model_options" />
            </FormFieldWrapper>
            <FormFieldWrapper id="model_name" label="Modelio pavadinimas">
              <Select v-model="model.options_model" :disabled="!model.use_model_options">
                <SelectTrigger>
                  <SelectValue placeholder="Pasirinkite modelį" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem v-for="opt in fieldModels" :key="opt.value" :value="opt.value">
                    {{ opt.label }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </FormFieldWrapper>
            <FormFieldWrapper id="model_field" label="Modelio laukas">
              <Select v-model="model.options_model_field" :disabled="!model.use_model_options">
                <SelectTrigger>
                  <SelectValue placeholder="Pasirinkite lauką" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem v-for="opt in fieldModelAttributes" :key="opt.value" :value="opt.value">
                    {{ opt.label }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </FormFieldWrapper>
          </div>
        </CollapsibleContent>
      </Collapsible>
    </template>
    <FormFieldWrapper id="is_required" label="Ar būtinas?" required>
      <Switch v-model="model.is_required" />
    </FormFieldWrapper>
    <FormFieldWrapper id="default_value" label="Automatiškai įrašomas atsakymas">
      <MultiLocaleInput v-model:input="model.default_value" />
    </FormFieldWrapper>
    <FormFieldWrapper id="placeholder" label="Pagalbinis tekstas laukelyje">
      <MultiLocaleInput v-model:input="model.placeholder" />
    </FormFieldWrapper>
    <Button @click="handleSubmit">
      Pateikti
    </Button>
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from "vue";
import { useForm } from "@inertiajs/vue3";

import { Button } from "@/Components/ui/button";
import { DynamicListInput } from "@/Components/ui/dynamic-list-input";
import { Input } from "@/Components/ui/input";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/Components/ui/select";
import { Switch } from "@/Components/ui/switch";
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from "@/Components/ui/collapsible";
import FormFieldWrapper from "./FormFieldWrapper.vue";
import MultiLocaleInput from "../FormItems/MultiLocaleInput.vue";
import MultiLocaleTiptapFormItem from "../FormItems/MultiLocaleTiptapFormItem.vue";

const emit = defineEmits<{
  (e: "submit", form: any): void;
}>();

const props = defineProps<{
  formField: App.Entities.FormField | Record<string, any>;
  hasRegistrations?: boolean;
  fieldModels?: { value: string; label: string }[];
  fieldModelAttributes?: { value: string; label: string }[];
}>();

const model = useForm(props.formField);
const advancedOpen = ref(false);

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
  emit("submit", model.data());
};
</script>
