<template>
  <div class="space-y-4">
    <FormFieldWrapper id="label" :label="$t('forms.fields.name')" required>
      <MultiLocaleInput v-model:input="model.label" />
    </FormFieldWrapper>

    <MultiLocaleTiptapFormItem
      v-model:input="model.description"
      :label="$t('Trumpas paaiškinimas')"
    />

    <FormFieldWrapper id="type" :label="$t('Tipas')" required>
      <Select v-model="model.type" :disabled="hasRegistrations">
        <SelectTrigger>
          <SelectValue :placeholder="$t('Pasirinkite tipą')" />
        </SelectTrigger>
        <SelectContent>
          <SelectItem v-for="opt in options" :key="opt.value" :value="opt.value">
            {{ opt.label }}
          </SelectItem>
        </SelectContent>
      </Select>
    </FormFieldWrapper>

    <FormFieldWrapper v-if="subtypeOptions.length > 0" id="subtype" :label="$t('Subtipas')">
      <Select v-model="model.subtype" :disabled="hasRegistrations">
        <SelectTrigger>
          <SelectValue :placeholder="$t('Pasirinkite subtipą')" />
        </SelectTrigger>
        <SelectContent>
          <SelectItem v-for="opt in subtypeOptions" :key="opt.value" :value="opt.value">
            {{ opt.label }}
          </SelectItem>
        </SelectContent>
      </Select>
    </FormFieldWrapper>

    <template v-if="model.type === 'enum'">
      <FormFieldWrapper v-if="!model.use_model_options" id="options" :label="$t('Reikšmės')">
        <DynamicListInput
          v-model="model.options"
          :create-item="onCreate"
          allow-empty
          :empty-text="$t('Nėra pridėtų reikšmių')"
          :add-first-text="$t('Pridėti pirmą reikšmę')"
          :add-text="$t('Pridėti reikšmę')"
        >
          <template #item="{ item }">
            <div class="flex flex-row items-center gap-2">
              <FormFieldWrapper id="value" :label="$t('Reikšmė')" required>
                <Input v-model="item.value" :disabled="hasRegistrations" />
              </FormFieldWrapper>
              <FormFieldWrapper id="label" :label="$t('Pavadinimas')" required>
                <MultiLocaleInput v-model:input="item.label" />
              </FormFieldWrapper>
            </div>
          </template>
        </DynamicListInput>
      </FormFieldWrapper>

      <Collapsible v-model:open="advancedOpen" class="mb-6">
        <CollapsibleTrigger as-child>
          <button
            type="button"
            class="u-touch flex w-full items-center justify-between border border-border bg-secondary px-3 py-2 text-sm font-semibold transition-colors hover:bg-accent"
          >
            <span>{{ $t('Papildomi nustatymai') }}</span>
            <ChevronDown
              class="size-4 text-muted-foreground transition-transform duration-200"
              :class="{ 'rotate-180': advancedOpen }"
            />
          </button>
        </CollapsibleTrigger>
        <CollapsibleContent>
          <div class="space-y-4 border border-t-0 border-border bg-card p-4">
            <FormFieldWrapper id="use_model_options" :label="$t('Naudoti iš duombazės?')" required>
              <Switch v-model="model.use_model_options" />
            </FormFieldWrapper>
            <FormFieldWrapper id="option_source" :label="$t('Duomenų šaltinis')">
              <Select v-model="model.option_source" :disabled="!model.use_model_options">
                <SelectTrigger>
                  <SelectValue :placeholder="$t('Pasirinkite modelį')" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem v-for="opt in fieldModels" :key="opt.value" :value="opt.value">
                    {{ opt.label }}
                  </SelectItem>
                </SelectContent>
              </Select>
            </FormFieldWrapper>
          </div>
        </CollapsibleContent>
      </Collapsible>
    </template>

    <FormFieldWrapper id="is_required" :label="$t('Ar būtinas?')" required>
      <Switch v-model="model.is_required" />
    </FormFieldWrapper>

    <FormFieldWrapper id="default_value" :label="$t('Automatiškai įrašomas atsakymas')">
      <MultiLocaleInput v-model:input="model.default_value" />
    </FormFieldWrapper>

    <FormFieldWrapper id="placeholder" :label="$t('Pagalbinis tekstas laukelyje')">
      <MultiLocaleInput v-model:input="model.placeholder" />
    </FormFieldWrapper>

    <div class="flex justify-end pt-2">
      <Button variant="brand" @click="handleSubmit">
        {{ $t('Pateikti') }}
      </Button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { ChevronDown } from 'lucide-vue-next';
import { computed, ref } from 'vue';

import MultiLocaleInput from '../FormItems/MultiLocaleInput.vue';
import MultiLocaleTiptapFormItem from '../FormItems/MultiLocaleTiptapFormItem.vue';

import FormFieldWrapper from './FormFieldWrapper.vue';

import { Button } from '@/Components/ui/button';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/Components/ui/collapsible';
import { DynamicListInput } from '@/Components/ui/dynamic-list-input';
import { Input } from '@/Components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Switch } from '@/Components/ui/switch';

const props = defineProps<{
  formField: App.Entities.FormField | Record<string, unknown>;
  hasRegistrations?: boolean;
  fieldModels?: { value: string; label: string }[];
}>();

const emit = defineEmits<{
  submit: [form: unknown];
}>();

const model = useForm(props.formField as Record<string, unknown>);
const advancedOpen = ref(false);

const options = [
  { value: 'string', label: $t('Tekstas') },
  { value: 'boolean', label: $t('Taip / Ne') },
  { value: 'enum', label: $t('Pasirinkimas') },
  { value: 'number', label: $t('Skaičius') },
  { value: 'date', label: $t('Data') },
];

const subtypeOptions = computed(() => {
  if (model.type === 'string') {
    return [
      { value: 'name', label: $t('Vardas') },
      { value: 'email', label: $t('El. paštas') },
      { value: 'textarea', label: $t('Ilgo teksto laukas') },
    ];
  }
  return [];
});

function onCreate() {
  return {
    value: '',
    label: { lt: '', en: '' },
  };
}

const handleSubmit = () => {
  emit('submit', model.data());
};
</script>
