<template>
  <SheetForm
    :open
    :title="$t('meetings.item.edit')"
    :processing="form.processing"
    :dirty
    @update:open="emit('update:open', $event)"
    @cancel="load"
    @submit="save"
  >
    <div class="flex flex-wrap items-center gap-3">
      <div :class="segmentGroupClass" role="group" :aria-label="$t('Kalba')">
        <button
          v-for="loc in LOCALES"
          :key="loc"
          type="button"
          :class="segmentVariants({ active: locale === loc })"
          :aria-pressed="locale === loc"
          :data-testid="`agenda-item-locale-${loc}`"
          @click="locale = loc"
        >
          <LocaleFlag :locale="loc" />
          <span>{{ loc.toUpperCase() }}</span>
        </button>
      </div>
      <p v-if="locale === 'en'" class="flex items-center gap-1.5 text-xs font-medium text-status-attention">
        <Languages class="size-3.5 shrink-0" />
        {{ $t('meetings.agenda.editing_english') }}
      </p>
    </div>

    <FormFieldWrapper id="agenda-item-title" :label="$t('meetings.item.title_label')" :error="titleError" required>
      <Textarea
        id="agenda-item-title"
        v-model="draft.title[locale]"
        rows="2"
        :class="['min-h-11 text-base', fieldSurfaceClass]"
        :placeholder="locale === 'en' ? $t('meetings.agenda.title_placeholder_en') : $t('Darbotvarkės punkto pavadinimas')"
      />
    </FormFieldWrapper>

    <FormFieldWrapper id="agenda-item-start-time" :label="$t('meetings.item.time')" :error="form.errors.end_time">
      <div class="flex items-center gap-2">
        <!-- TimePicker, not <input type="time">: the native control shows AM/PM in an English browser. -->
        <TimePicker
          id="agenda-item-start-time"
          :model-value="toTimeValue(draft.start_time)"
          :minute-step="5"
          clearable
          class="h-11 w-[6.5rem] text-sm"
          :aria-label="$t('Kada klausimas pradedamas svarstyti')"
          @update:model-value="(value) => draft.start_time = toTimeString(value)"
        />
        <span class="text-muted-foreground" aria-hidden="true">–</span>
        <TimePicker
          :model-value="toTimeValue(draft.end_time)"
          :minute-step="5"
          clearable
          class="h-11 w-[6.5rem] text-sm"
          :aria-label="$t('Kada klausimo svarstymas baigiamas')"
          @update:model-value="(value) => draft.end_time = toTimeString(value)"
        />
      </div>
    </FormFieldWrapper>

    <label class="flex cursor-pointer items-center gap-3 text-sm text-foreground pointer-coarse:min-h-11">
      <Checkbox v-model="draft.brought_by_students" />
      {{ $t('meetings.item.brought_by_students') }}
    </label>

    <FormFieldWrapper id="agenda-item-description" :label="$t('meetings.item.description')" :hint="publicHint">
      <Textarea
        id="agenda-item-description"
        v-model="draft.description[locale]"
        rows="5"
        :class="fieldSurfaceClass"
      />
    </FormFieldWrapper>

    <FormFieldWrapper
      v-if="requiresStudentPerspective"
      id="agenda-item-student-position"
      :label="$t('meetings.item.student_position')"
      :hint="publicHint"
    >
      <Textarea
        id="agenda-item-student-position"
        v-model="draft.student_position[locale]"
        rows="5"
        :class="fieldSurfaceClass"
      />
    </FormFieldWrapper>

    <template v-if="canDelete" #danger-zone>
      <Button variant="ghost" voice="sentence" class="text-destructive hover:text-destructive" @click="emit('delete')">
        <Trash2 class="size-4" />
        {{ $t('meetings.item.delete') }}
      </Button>
    </template>
  </SheetForm>
</template>

<script setup lang="ts">
import { computed, reactive, ref, watch } from 'vue';
import type { InertiaForm } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { Languages, Trash2 } from 'lucide-vue-next';

import FormFieldWrapper from '@/Components/AdminForms/FormFieldWrapper.vue';
import { SheetForm } from '@/Components/Patterns';
import LocaleFlag from '@/Components/Public/Nav/LocaleFlag.vue';
import { Button } from '@/Components/ui/button';
import { Checkbox } from '@/Components/ui/checkbox';
import { fieldSurfaceClass, segmentGroupClass, segmentVariants } from '@/Components/ui/control';
import { Textarea } from '@/Components/ui/textarea';
import { TimePicker, type TimeValue } from '@/Components/ui/time-picker';
import type { AgendaItemFormData } from '@/Composables/useAgendaItemAutosave';

const props = withDefaults(defineProps<{
  open: boolean;
  /** The live, autosaving form: the sheet edits a draft and writes it back on save. */
  form: InertiaForm<AgendaItemFormData>;
  /** Saves the form, then calls back once the server accepted it. */
  saveThen: (callback: () => void) => void;
  /** Pre-fills an empty start time, e.g. from the previous item's end. */
  defaultStartTime?: string | null;
  requiresStudentPerspective?: boolean;
  isPublic?: boolean;
  canDelete?: boolean;
}>(), {
  defaultStartTime: null,
  requiresStudentPerspective: true,
  isPublic: false,
  canDelete: false,
});

const emit = defineEmits<{
  'update:open': [value: boolean];
  'delete': [];
}>();

const LOCALES = ['lt', 'en'] as const;

/** Votes are edited in AgendaItemVotesSheetForm, so this sheet stays about the item itself. */
type TextDraft = Pick<AgendaItemFormData, 'title' | 'brought_by_students' | 'description' | 'student_position' | 'start_time' | 'end_time'>;

function clone<T>(value: T): T {
  return JSON.parse(JSON.stringify(value)) as T;
}

const fromForm = (): TextDraft => clone({
  title: props.form.title,
  brought_by_students: props.form.brought_by_students,
  description: props.form.description,
  student_position: props.form.student_position,
  start_time: props.form.start_time ?? props.defaultStartTime,
  end_time: props.form.end_time,
});

const draft = reactive<TextDraft>(fromForm());
const baseline = ref(JSON.stringify(draft));
const locale = ref<'lt' | 'en'>('lt');

const load = () => {
  Object.assign(draft, fromForm());
  baseline.value = JSON.stringify(draft);
  locale.value = 'lt';
};

watch(() => props.open, (open) => {
  if (open) {
    load();
  }
});

const dirty = computed(() => JSON.stringify(draft) !== baseline.value);

const titleError = computed(() => props.form.errors[`title.${locale.value}` as keyof typeof props.form.errors]
  ?? props.form.errors.title);

/** The form holds `HH:MM` strings; TimePicker speaks {hour, minute}. */
const toTimeValue = (value: string | null): TimeValue | undefined => {
  if (!value) return undefined;
  const [hour, minute] = value.split(':');

  return { hour: Number(hour), minute: Number(minute) };
};

const toTimeString = (value: TimeValue | undefined): string | null =>
  value
    ? `${String(value.hour).padStart(2, '0')}:${String(value.minute).padStart(2, '0')}`
    : null;

const publicHint = computed(() => (props.isPublic ? $t('meetings.record.visible_public') : undefined));

const save = () => {
  props.form.title = clone(draft.title);
  props.form.brought_by_students = draft.brought_by_students;
  props.form.description = clone(draft.description);
  props.form.student_position = clone(draft.student_position);
  props.form.start_time = draft.start_time;
  props.form.end_time = draft.end_time;

  // A rejected save never calls back, so the sheet stays open on its errors.
  props.saveThen(() => {
    baseline.value = JSON.stringify(draft);
    emit('update:open', false);
  });
};
</script>
