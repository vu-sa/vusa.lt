<template>
  <AdminForm :model="form" label-placement="top" @submit:form="$emit('submit:form', form)" @delete="$emit('delete')">
    <FormElement>
      <template #title>
        {{ $t('forms.context.dutiable_intro_title') }}
      </template>
      <template #description>
        <p>{{ $t('forms.helpers.dutiable_intro') }}</p>
      </template>

      <div class="flex items-start gap-3 rounded-lg border bg-muted/30 p-3">
        <img v-if="previewPhoto" :src="previewPhoto" class="size-16 shrink-0 rounded-md object-cover"
          :style="{ objectPosition: previewFocalPoint }" alt="">
        <div v-else class="flex size-16 shrink-0 items-center justify-center rounded-md bg-muted text-sm font-bold text-muted-foreground">
          {{ previewInitials }}
        </div>
        <div class="min-w-0">
          <p class="text-[10px] font-medium uppercase tracking-wide text-muted-foreground">
            {{ $t('forms.helpers.dutiable_preview_label') }}
          </p>
          <p class="flex items-center gap-2 truncate text-sm font-semibold">
            {{ personName }}
            <Badge v-if="isExOfficio" data-testid="ex-officio-badge" variant="secondary" class="gap-1 text-[10px] font-medium">
              <Sparkles class="size-3 shrink-0" />
              {{ $t('forms.fields.ex_officio_badge') }}
            </Badge>
          </p>
          <p class="text-xs text-muted-foreground">
            {{ shownDutyName }}<span v-if="previewStudyProgramSuffix"> {{ previewStudyProgramSuffix }}</span>
          </p>
          <div v-if="previewDescriptionHtml" data-testid="dutiable-description-preview" class="mt-1 text-xs text-muted-foreground" v-html="previewDescriptionHtml" />
        </div>
      </div>
    </FormElement>

    <FormElement>
      <template #title>
        {{ $t('forms.sections.duty_period') }}
      </template>
      <template #description>
        {{ $t('forms.helpers.duty_period_info') }}
      </template>
      <Alert v-if="isExOfficio" data-testid="ex-officio-notice" class="mb-4">
        <Sparkles class="size-4" />
        <AlertTitle>{{ $t('forms.fields.ex_officio_notice', { duty: exOfficioSourceName }) }}</AlertTitle>
        <AlertDescription>
          <p>{{ $t('forms.fields.ex_officio_period_managed') }}</p>
          <Link v-if="exOfficioSourceDutyId" :href="route('duties.edit', exOfficioSourceDutyId)" class="mt-1 inline-block underline underline-offset-2">
            {{ $t('forms.fields.ex_officio_source_link') }}
          </Link>
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
        <p v-if="missingRequiredStudyProgram" class="mt-1 flex items-center gap-1 text-xs text-amber-600 dark:text-amber-400">
          <TriangleAlert class="size-3 shrink-0" />
          {{ $t('forms.helpers.study_program_required_hint') }}
        </p>
      </FormFieldWrapper>

      <div class="space-y-2">
        <div class="inline-flex items-center gap-2">
          <Label for="description">{{ $t('forms.fields.description') }}</Label>
          <SimpleLocaleButton v-model:locale="locale" />
        </div>
        <p class="text-xs text-muted-foreground">
          {{ $t('forms.helpers.dutiable_description_hint') }}
        </p>
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
import { Sparkles, TriangleAlert } from 'lucide-vue-next';

import SimpleLocaleButton from '../Buttons/SimpleLocaleButton.vue';

import FormElement from './FormElement.vue';
import FormFieldWrapper from './FormFieldWrapper.vue';
import AdminForm from './AdminForm.vue';

import TiptapEditor from '@/Components/TipTap/TiptapEditor.vue';
import { Alert, AlertDescription, AlertTitle } from '@/Components/ui/alert';
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

const exOfficioSourceDutyId = computed(() => props.dutiable.via_dutiable?.duty?.id ?? null);

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

// vusa.lt/mano is admin-only; the public site always shows the study program in
// brackets after the duty name (ContactWithPhoto.vue), regardless of the duty's
// own `contacts_grouping` setting — the field is not "grouping-only".
const previewStudyProgramSuffix = computed(() => {
  if (!selectedStudyProgram.value) return '';
  return `(${selectedStudyProgram.value.name})`;
});

// A duty that groups its public contacts by study program has nothing to group
// an assignment into if this is left empty — flag it, rather than let it render
// silently under "Kita" (the ungrouped fallback bucket).
const missingRequiredStudyProgram = computed(() =>
  props.dutiable.duty?.contacts_grouping === 'study_program' && !form.study_program_id);

const personName = computed(() => (props.dutiable.dutiable as any)?.name ?? '');

const previewPhoto = computed(() =>
  (form.additional_photo as string | null) || (props.dutiable.dutiable as any)?.profile_photo_path || null);

const previewFocalPoint = computed(() =>
  (props.dutiable as any).additional_photo_focal_point || (props.dutiable.dutiable as any)?.profile_photo_focal_point || '50% 30%');

const previewInitials = computed(() => {
  const parts = personName.value.split(' ').filter(Boolean);
  if (parts.length >= 2) return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
  return parts[0]?.substring(0, 2).toUpperCase() ?? '?';
});

// Public rendering shows the assignment's own description in place of the duty's
// (ContactWithPhoto.vue) — the preview mirrors exactly that precedence, live.
// Emptiness is measured through textContent, not a tag-stripping regex: a single
// regex pass cannot reliably strip nested markup, and CodeQL flags the pattern.
const previewDescriptionHtml = computed(() => {
  const html = (form.description as { lt?: string; en?: string })?.[usePage().props.app.locale as 'lt' | 'en']
    || (form.description as { lt?: string })?.lt
    || '';
  if (!html) return '';

  const container = document.createElement('div');
  container.innerHTML = html;
  if (!(container.textContent ?? '').trim()) return '';

  return html;
});
</script>
