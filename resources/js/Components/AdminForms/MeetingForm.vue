<template>
  <SheetForm
    :created-at="meeting?.created_at"
    :updated-at="meeting?.updated_at"
    :open
    :title="$t('Redaguoti posėdį')"
    :processing="form.processing"
    :dirty="form.isDirty"
    @update:open="emit('update:open', $event)"
    @cancel="resetForm"
    @submit="save"
  >
    <FormFieldWrapper id="meeting-type" :label="$tChoice('forms.fields.type', 0)" :error="form.errors.type">
      <RadioGroup :model-value="form.type" class="mt-2 space-y-2" @update:model-value="form.type = $event as MeetingTypeValue | '__null__'">
        <div v-for="typeOption in meetingTypeOptions" :key="typeOption.value ?? 'null'" class="flex min-h-11 items-center gap-2">
          <RadioGroupItem :id="`meeting-type-${typeOption.value ?? 'null'}`" :value="typeOption.value ?? '__null__'" />
          <Label :for="`meeting-type-${typeOption.value ?? 'null'}`" class="flex cursor-pointer items-center gap-2">
            {{ typeOption.label }}
            <span v-if="typeOption.isDateOnly" class="text-xs text-muted-foreground">({{ $t('tik data') }})</span>
          </Label>
        </div>
      </RadioGroup>
    </FormFieldWrapper>

    <FormFieldWrapper
      v-if="!isEmailMeeting(form.type)"
      id="meeting-kind"
      :label="$t('forms.fields.meeting_kind_override')"
      :hint="$t('forms.helpers.meeting_kind_meeting_hint')"
      :error="form.errors.meeting_kind"
    >
      <NativeSelect
        id="meeting-kind"
        v-model="meetingKind"
        :aria-label="$t('forms.fields.meeting_kind_override')"
        :options="meetingKindChoices"
      />
    </FormFieldWrapper>

    <FormFieldWrapper
      id="meeting-start-time"
      :label="isEmailMeeting(form.type) ? $t('forms.fields.date') : `${$t('forms.fields.date')} / ${$t('forms.fields.time')}`"
      :error="form.errors.start_time"
      required
    >
      <DatePicker
        v-if="isEmailMeeting(form.type)"
        :model-value="form.start_time"
        class="w-full"
        @update:model-value="form.start_time = $event"
      />
      <DateTimePicker
        v-else
        :model-value="form.start_time"
        :hour-range="[7, 22]"
        :minute-step="5"
        class="w-full"
        @update:model-value="form.start_time = $event"
      />
      <p v-if="isWeekendTime(form.start_time)" class="mt-2 text-sm text-status-attention">
        {{ $t('Pasirinktas savaitgalio laikas') }}
      </p>
    </FormFieldWrapper>

    <FormFieldWrapper id="meeting-description" :label="$t('Aprašymas')" :error="form.errors.description">
      <SimpleLocaleButton :locale="descriptionLocale" @update:locale="descriptionLocale = $event" />
      <Textarea
        id="meeting-description"
        v-model="form.description[descriptionLocale]"
        :placeholder="$t('Trumpas posėdžio aprašymas...')"
        :class="fieldSurfaceClass"
        rows="3"
      />
    </FormFieldWrapper>
  </SheetForm>
</template>

<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { trans as $t, transChoice as $tChoice } from 'laravel-vue-i18n';
import { computed, ref, watch } from 'vue';

import FormFieldWrapper from '@/Components/AdminForms/FormFieldWrapper.vue';
import SimpleLocaleButton from '@/Components/Buttons/SimpleLocaleButton.vue';
import { SheetForm } from '@/Components/Patterns';
import { fieldSurfaceClass } from '@/Components/ui/control';
import { DatePicker, DateTimePicker } from '@/Components/ui/date-picker';
import { Label } from '@/Components/ui/label';
import { NativeSelect } from '@/Components/ui/native-select';
import { RadioGroup, RadioGroupItem } from '@/Components/ui/radio-group';
import { Textarea } from '@/Components/ui/textarea';
import { useMeetingForm } from '@/Composables/useMeetingForm';
import { meetingKindLabel, meetingKindOptions } from '@/Constants/meetingKinds';
import { MeetingKind } from '@/Types/enums';
import type { MeetingTypeValue } from '@/Types/MeetingType';

const props = defineProps<{
  open: boolean;
  meeting: {
    id: string;
    start_time: string;
    type: MeetingTypeValue;
    meeting_kind: MeetingKind | null;
    inherited_meeting_kind: MeetingKind;
    description: { lt: string; en: string };
    created_at: string;
    updated_at: string;
  };
}>();

const emit = defineEmits<{
  'update:open': [value: boolean];
}>();

const { meetingTypeOptions, isEmailMeeting, isWeekendTime, formatMeetingData, getInitialValues } = useMeetingForm();
const initial = getInitialValues(props.meeting);
const form = useForm({
  start_time: initial.start_time as Date | undefined,
  type: (initial.type ?? '__null__') as MeetingTypeValue | '__null__',
  description: initial.description,
  meeting_kind: (props.meeting.meeting_kind ?? null) as MeetingKind | null,
});
const descriptionLocale = ref<'lt' | 'en'>('lt');
const FROM_INSTITUTION = '__institution__';
const meetingKindChoices = computed(() => [
  {
    value: FROM_INSTITUTION,
    label: $t('forms.options.meeting_kind_from_institution', {
      kind: meetingKindLabel(props.meeting.inherited_meeting_kind ?? MeetingKind.Sitting),
    }),
  },
  ...meetingKindOptions(),
]);
const meetingKind = computed({
  get: () => form.meeting_kind ?? FROM_INSTITUTION,
  set: (value: string | number | null | undefined) => {
    form.meeting_kind = value === FROM_INSTITUTION ? null : value as MeetingKind;
  },
});

function resetForm(): void {
  form.reset();
  form.clearErrors();
  descriptionLocale.value = 'lt';
}

watch(() => props.open, (open) => {
  if (!open) return;
  const values = getInitialValues(props.meeting);
  form.defaults({
    start_time: values.start_time,
    type: values.type ?? '__null__',
    description: values.description,
    meeting_kind: props.meeting.meeting_kind ?? null,
  });
  resetForm();
});

function save(): void {
  if (!form.start_time) {
    form.setError('start_time', $t('validation.required', { attribute: $t('forms.fields.date') }));
    return;
  }

  form.transform(data => ({
    ...formatMeetingData({ ...data, start_time: data.start_time!, type: data.type === '__null__' ? null : data.type }),
    meeting_kind: data.meeting_kind,
  }))
    .patch(route('meetings.update', props.meeting.id), {
      preserveScroll: true,
      onSuccess: () => emit('update:open', false),
    });
}
</script>
