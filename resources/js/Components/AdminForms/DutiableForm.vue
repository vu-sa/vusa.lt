<template>
  <AdminForm :model="form" label-placement="top" @submit:form="$emit('submit:form', form)" @delete="$emit('delete')">
    <FormElement>
      <template #title>
        Pareigybės ėjimo laikotarpis
      </template>
      <template #description>
        Šioje vietoje nustatomas pareigybės ėjimo laikotarpis. Kai žymima
        pareigybės laikotarpio ėjimo pabaiga, pareigybės pavadinimas bus
        giminizuotas pagal vardą ir pavardę.
      </template>
      <FormFieldWrapper id="start_date" label="Pareigų ėjimo pradžia" required :error="form.errors.start_date">
        <DatePicker v-model="form.start_date" />
      </FormFieldWrapper>
      <FormFieldWrapper id="end_date" label="Pareigų ėjimo pabaiga" required :error="form.errors.end_date">
        <DatePicker v-model="form.end_date" />
      </FormFieldWrapper>
    </FormElement>
    <FormElement>
      <template #title>
        Papildoma informacija
      </template>
      <FormFieldWrapper id="additional_email" label="Papildomas paštas" hint="Rodomas šis paštas vietoje vartotojo pašto" :error="form.errors.additional_email">
        <Input id="additional_email" v-model="form.additional_email" placeholder="petras.petraitis@vusa.lt" />
      </FormFieldWrapper>
      <FormFieldWrapper id="additional_photo" label="Papildoma nuotrauka" hint="Ši nuotrauka leidžia vienam asmeniui turėti daugiau negu vieną nuotrauką, kuri rodoma, kai puslapyje asmuo vaizduojamas su šia pareigybe." :error="form.errors.additional_photo">
        <ImageUpload v-model:url="form.additional_photo" mode="immediate" folder="contacts" cropper :existing-url="dutiable?.additional_photo" />
      </FormFieldWrapper>
      <FormFieldWrapper id="study_program_id" label="Studijų programa" hint="Kai aktualu, galima pasirinkti studijų programą, kurią rodo prie įrašo" :error="form.errors.study_program_id">
        <Select v-model="studyProgramIdString">
          <SelectTrigger>
            <SelectValue placeholder="Studijų programa" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem v-for="program in studyPrograms" :key="program.id" :value="String(program.id)">
              <span class="flex items-center gap-2">
                {{ program.name }}
                <Badge v-if="program.degree" variant="outline" class="text-xs">{{ program.degree }}</Badge>
              </span>
            </SelectItem>
          </SelectContent>
        </Select>
      </FormFieldWrapper>

      <div class="space-y-2">
        <div class="inline-flex items-center gap-2">
          <Label for="description">Aprašymas</Label>
          <SimpleLocaleButton v-model:locale="locale" />
        </div>
        <TiptapEditor v-if="locale === 'lt'" v-model="form.description.lt" preset="full" :html="true" />
        <TiptapEditor v-else-if="locale === 'en'" v-model="form.description.en" preset="full" :html="true" />
        <p v-if="form.errors.description" class="text-xs text-red-600 dark:text-red-400">
          {{ form.errors.description }}
        </p>
      </div>
    </FormElement>
    <FormElement>
      <template #title>
        Įvardžiai
      </template>
      <template #description>
        <p>
          Asmens įvardžius gali nusistatyti pats asmuo, prisijungęs prie mano.vusa.lt, savo nustatymų lange. Taip pat
          galima nustatyti
          <Link :href="route('users.edit', dutiable?.dutiable_id)">
            čia
          </Link>.
        </p>
        <p>
          Pareigos pavadinimas yra rodomas taip: <strong> {{ shownDutyName }}</strong>
        </p>
      </template>
      <FormFieldWrapper id="use_original_duty_name" label="Pareigos pavadinimo galūnės negiminizavimas" hint="Išjungia automatinį šios kontakto pareigybės giminizavimą pagal vardą ir pavardę. Bus naudojamas originalus pareigybės pavadinimas." :error="form.errors.use_original_duty_name">
        <Switch :checked="!!form.use_original_duty_name" @update:checked="(val: boolean) => form.use_original_duty_name = val" />
      </FormFieldWrapper>
    </FormElement>
  </AdminForm>
</template>

<script setup lang="ts">
import { Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

import SimpleLocaleButton from '../Buttons/SimpleLocaleButton.vue';

import FormElement from './FormElement.vue';
import FormFieldWrapper from './FormFieldWrapper.vue';
import AdminForm from './AdminForm.vue';

import TiptapEditor from '@/Components/TipTap/TiptapEditor.vue';
import { Badge } from '@/Components/ui/badge';
import { DatePicker } from '@/Components/ui/date-picker';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/Components/ui/select';
import { Switch } from '@/Components/ui/switch';
import { ImageUpload } from '@/Components/ui/upload';
import { changeDutyNameEndings } from '@/Utils/String';

const props = defineProps<{
  dutiable: App.Entities.Dutiable;
  studyPrograms: App.Entities.StudyProgram[];
  rememberKey?: string;
}>();

defineEmits<{
  (event: 'submit:form', form: unknown): void;
  (event: 'delete'): void;
}>();

const form = props.rememberKey
  ? useForm(props.rememberKey, props.dutiable as any)
  : useForm(props.dutiable as any);

if (Array.isArray(form.description)) {
  form.description = { lt: '', en: '' };
}

const locale = ref('lt');

// Shadcn Select requires string values
const studyProgramIdString = computed({
  get: () => form.study_program_id != null ? String(form.study_program_id) : '',
  set: (val: string) => { form.study_program_id = val ? Number(val) : null; },
});

const shownDutyName = computed(() => {
  if (!props.dutiable.duty?.name || !props.dutiable.dutiable) return '';

  return changeDutyNameEndings(
    props.dutiable.dutiable as any,
    props.dutiable.duty.name,
    usePage().props.app.locale,
    (props.dutiable.dutiable as any)?.pronouns,
    form.use_original_duty_name as boolean,
  );
});
</script>
