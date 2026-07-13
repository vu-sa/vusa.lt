<template>
  <AdminForm :model="form" label-placement="top" @submit:form="$emit('submit:form', form)" @delete="$emit('delete')">
    <FormElement>
      <template #title>
        {{ $t('forms.sections.duty_period') }}
      </template>
      <template #description>
        {{ $t('forms.helpers.duty_period_info') }}
      </template>
      <Alert v-if="isExOfficio" class="mb-4">
        <AlertDescription>
          {{ $t('forms.fields.ex_officio_notice', { duty: exOfficioSourceName }) }}
        </AlertDescription>
      </Alert>
      <FormFieldWrapper id="start_date" :label="$t('forms.fields.duty_start_date')" required :error="form.errors.start_date">
        <DatePicker v-model="form.start_date" :disabled="isExOfficio" />
      </FormFieldWrapper>
      <FormFieldWrapper id="end_date" :label="$t('forms.fields.duty_end_date')" required :error="form.errors.end_date">
        <DatePicker v-model="form.end_date" :disabled="isExOfficio" />
      </FormFieldWrapper>
    </FormElement>
    <FormElement>
      <template #title>
        {{ $t('forms.context.additional_info') }}
      </template>
      <FormFieldWrapper id="additional_email" :label="$t('forms.fields.additional_email')" :hint="$t('forms.helpers.additional_email_hint')" :error="form.errors.additional_email">
        <Input id="additional_email" v-model="form.additional_email" placeholder="petras.petraitis@vusa.lt" />
      </FormFieldWrapper>
      <FormFieldWrapper id="additional_photo" :label="$t('forms.fields.additional_photo')" :hint="$t('forms.helpers.additional_photo_hint')" :error="form.errors.additional_photo">
        <ImageUpload
          v-model:url="form.additional_photo"
          mode="immediate"
          folder="contacts"
          cropper
          preview-aspect="4/3"
          :existing-url="dutiable?.additional_photo"
        />
      </FormFieldWrapper>
      <FormFieldWrapper id="study_program_id" :label="$t('forms.fields.study_program')" :hint="$t('forms.helpers.study_program_hint')" :error="form.errors.study_program_id">
        <SingleSelect
          v-model="selectedStudyProgram"
          :options="studyPrograms"
          label-field="name"
          value-field="id"
          :placeholder="$t('forms.placeholders.study_program')"
        >
          <template #option="{ item }">
            <span class="flex items-center gap-2">
              {{ item.name }}
              <Badge v-if="item.degree" variant="outline" class="text-xs">{{ item.degree }}</Badge>
            </span>
          </template>
        </SingleSelect>
      </FormFieldWrapper>

      <div class="space-y-2">
        <div class="inline-flex items-center gap-2">
          <Label for="description">{{ $t('forms.fields.description') }}</Label>
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
        {{ $t('forms.fields.pronouns') }}
      </template>
      <template #description>
        <p>
          {{ $t('forms.helpers.pronouns_self_set') }}
          <Link :href="route('users.edit', dutiable?.dutiable_id)">
            {{ $t('čia') }}
          </Link>.
        </p>
        <p>
          {{ $t('forms.helpers.shown_duty_name_label') }} <strong> {{ shownDutyName }}</strong>
        </p>
      </template>
      <FormFieldWrapper id="use_original_duty_name" :label="$t('forms.fields.use_original_duty_name')" :hint="$t('forms.helpers.use_original_duty_name_hint')" :error="form.errors.use_original_duty_name">
        <Switch :model-value="!!form.use_original_duty_name" @update:model-value="(val: boolean) => form.use_original_duty_name = val" />
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
import { Alert, AlertDescription } from '@/Components/ui/alert';
import { Badge } from '@/Components/ui/badge';
import { DatePicker } from '@/Components/ui/date-picker';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { SingleSelect } from '@/Components/ui/single-select';
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

const isExOfficio = computed(() => !!props.dutiable.via_dutiable_id);

const exOfficioSourceName = computed(() => {
  const name = props.dutiable.via_dutiable?.duty?.name as string | Record<string, string> | null | undefined;
  if (!name) return '';
  if (typeof name === 'string') return name;
  return name[usePage().props.app.locale] ?? name.lt ?? '';
});

// Bridge: SingleSelect operates on full objects, form stores study_program_id for server submission
const selectedStudyProgram = computed({
  get: () => props.studyPrograms.find(p => p.id === form.study_program_id) ?? null,
  set: (val: App.Entities.StudyProgram | null) => { form.study_program_id = val?.id ?? null; },
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
