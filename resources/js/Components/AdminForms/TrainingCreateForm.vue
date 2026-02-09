<template>
  <AdminForm :model="form" label-placement="top" @submit:form="$emit('submit:form', form)" @delete="$emit('delete')">
    <FormElement>
      <template #title>
        {{ $t("forms.context.main_info") }}
      </template>
      <template #description>
        <p>
          Užpildžius šią informaciją, bus sukurtas mokymų juodraštis, kuriame bus galima užpildyti pilną mokymų
          informaciją.
        </p>
        <p>
          Įrašytą informaciją bus galima pakeisti.
        </p>
      </template>
      <FormFieldWrapper id="name" label="Pavadinimas" required :error="form.errors.name">
        <MultiLocaleInput v-model:input="form.name" />
      </FormFieldWrapper>
      <div class="space-y-2">
        <div class="inline-flex items-center gap-2">
          <Label for="description">Aprašymas</Label>
          <span class="text-red-500">*</span>
          <SimpleLocaleButton v-model:locale="locale" />
        </div>
        <TiptapEditor v-if="locale === 'lt'" v-model="form.description.lt" preset="full" :html="true" />
        <TiptapEditor v-else v-model="form.description.en" preset="full" :html="true" />
        <p v-if="form.errors.description" class="text-xs text-red-600 dark:text-red-400">
          {{ form.errors.description }}
        </p>
      </div>
      <FormFieldWrapper id="institution_id" :label="$t('Kas organizuoja mokymus?')" required :error="form.errors.institution_id">
        <Select v-model="institutionIdString">
          <SelectTrigger>
            <SelectValue :placeholder="$t('Pasirinkite instituciją')" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem v-for="institution in institutions" :key="institution.value" :value="String(institution.value)">
              {{ institution.label }}
            </SelectItem>
          </SelectContent>
        </Select>
      </FormFieldWrapper>
      <FormFieldWrapper id="start_time" label="Preliminari mokymų pradžia" required :error="form.errors.start_time">
        <DateTimePicker v-model="form.start_time" :hour-range="[8, 22]" :minute-step="5" />
      </FormFieldWrapper>
    </FormElement>
  </AdminForm>
</template>

<script setup lang="ts">
import { trans as $t } from 'laravel-vue-i18n';
import { useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

import MultiLocaleInput from '../FormItems/MultiLocaleInput.vue';
import SimpleLocaleButton from '../Buttons/SimpleLocaleButton.vue';

import FormElement from './FormElement.vue';
import FormFieldWrapper from './FormFieldWrapper.vue';
import AdminForm from './AdminForm.vue';

import { DateTimePicker } from '@/Components/ui/date-picker';
import { Label } from '@/Components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import TiptapEditor from '@/Components/TipTap/TiptapEditor.vue';

const { training } = defineProps<{
  training: App.Entities.Membership;
}>();

defineEmits<{
  (event: 'submit:form', form: unknown): void;
  (event: 'delete'): void;
}>();

const form = useForm('CreateTraining', training);
const locale = ref('lt');

// Shadcn Select requires string values
const institutionIdString = computed({
  get: () => form.institution_id != null ? String(form.institution_id) : '',
  set: (val: string) => { form.institution_id = val ? Number(val) : null; },
});

// NOTE: Duplicated in InstitutionSelectorForm.vue
const institutions = computed(() => {
  return usePage()
    .props.auth?.user?.current_duties?.map((duty) => {
      if (!duty.institution) {
        return;
      }

      return {
        label: duty.institution?.name,
        value: duty.institution?.id,
      };
    })
    // filter unique
    .filter(institution => institution !== undefined).filter(
      (value, index, self) =>
        self.findIndex(t => t?.value === value?.value) === index,
    );
});
</script>
