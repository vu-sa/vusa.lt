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

    <div class="space-y-3 border-y border-border py-4" data-slot="agenda-item-privacy">
      <label class="flex cursor-pointer items-center gap-3 text-sm pointer-coarse:min-h-11">
        <Switch id="agenda-item-private" v-model="draft.is_private" aria-describedby="agenda-item-privacy-lead" />
        {{ $t('meetings.privacy.switch_label') }}
      </label>
      <p id="agenda-item-privacy-lead" class="text-sm text-muted-foreground">
        {{ $t(draft.is_private ? 'meetings.privacy.enabled_lead' : 'meetings.privacy.disabled_lead') }}
      </p>
      <details class="group" data-testid="agenda-item-privacy-details">
        <summary :class="[
          'u-touch inline-flex cursor-pointer items-center gap-1.5',
          'select-none text-xs font-bold uppercase tracking-wide text-foreground/80 transition-colors hover:text-foreground',
        ]">
          <ChevronDown class="size-3.5 transition-transform group-open:rotate-180" />
          {{ $t('meetings.privacy.details_label') }}
        </summary>
        <div class="mt-3 space-y-3 text-sm text-muted-foreground">
          <p v-if="!isPublic">
            {{ $t('meetings.privacy.nonpublic_hint') }}
          </p>
          <p>{{ $t('meetings.privacy.audience') }}</p>
          <p>{{ $t('meetings.privacy.no_personal_data') }}</p>
        </div>
      </details>
      <FormFieldWrapper
        v-if="draft.is_private"
        id="agenda-item-public-title"
        :label="$t('meetings.privacy.public_title')"
        :hint="$t('meetings.privacy.public_title_hint')"
        :error="form.errors[`public_title.${locale}`]"
      >
        <Input id="agenda-item-public-title" v-model="draft.public_title[locale]" maxlength="200" :class="fieldSurfaceClass" />
      </FormFieldWrapper>
      <div v-if="draft.is_private" class="border-l-2 border-border pl-3 text-sm" data-testid="agenda-item-public-preview">
        <p class="text-xs text-muted-foreground">
          {{ $t('meetings.privacy.public_preview') }}
        </p>
        <p>
          <span v-if="order" class="mr-2 font-mono text-muted-foreground">{{ order }}.</span>
          {{ draft.public_title[locale]?.trim() || draft.public_title.lt?.trim() || $t('meetings.privacy.hidden_title') }}
        </p>
        <span class="text-xs text-muted-foreground">{{ $t('meetings.privacy.internal_only') }}</span>
      </div>
      <p v-else-if="form.is_private" class="text-sm text-status-attention">
        {{ $t('meetings.privacy.publish_hint') }}
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
          :suggest-from="startSuggestionsFrom"
          clearable
          class="h-11 w-32 text-sm"
          :aria-label="$t('Kada klausimas pradedamas svarstyti')"
          @update:model-value="(value) => draft.start_time = toTimeString(value)"
        />
        <span class="text-muted-foreground" aria-hidden="true">–</span>
        <TimePicker
          :model-value="toTimeValue(draft.end_time)"
          :minute-step="5"
          :suggest-from="toTimeValue(draft.start_time) ?? startSuggestionsFrom"
          clearable
          class="h-11 w-32 text-sm"
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
import { computed, nextTick, reactive, ref, watch } from 'vue';
import type { InertiaForm } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { ChevronDown, Languages, Trash2 } from 'lucide-vue-next';

import FormFieldWrapper from '@/Components/AdminForms/FormFieldWrapper.vue';
import { SheetForm } from '@/Components/Patterns';
import LocaleFlag from '@/Components/Public/Nav/LocaleFlag.vue';
import { Button } from '@/Components/ui/button';
import { Checkbox } from '@/Components/ui/checkbox';
import { Switch } from '@/Components/ui/switch';
import { Input } from '@/Components/ui/input';
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
  /** `HH:MM`; the time suggestions start here when there is no earlier item to follow. */
  meetingStartTime?: string | null;
  requiresStudentPerspective?: boolean;
  isPublic?: boolean;
  canDelete?: boolean;
  order?: number;
}>(), {
  defaultStartTime: null,
  meetingStartTime: null,
  requiresStudentPerspective: true,
  isPublic: false,
  canDelete: false,
  order: undefined,
});

const emit = defineEmits<{
  'update:open': [value: boolean];
  'delete': [];
}>();

const LOCALES = ['lt', 'en'] as const;

/** Votes are edited in AgendaItemVotesSheetForm, so this sheet stays about the item itself. */
type TextDraft = Pick<AgendaItemFormData, 'title' | 'is_private' | 'public_title' | 'brought_by_students' | 'description' | 'student_position' | 'start_time' | 'end_time'>;

function clone<T>(value: T): T {
  return JSON.parse(JSON.stringify(value)) as T;
}

const fromForm = (): TextDraft => clone({
  title: props.form.title,
  is_private: props.form.is_private ?? false,
  public_title: props.form.public_title ?? { lt: '', en: '' },
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

// Items are discussed during the meeting, so the list opens there rather than at midnight.
const startSuggestionsFrom = computed<TimeValue>(() =>
  toTimeValue(props.defaultStartTime) ?? toTimeValue(props.meetingStartTime) ?? { hour: 8, minute: 0 });

const toTimeString = (value: TimeValue | undefined): string | null =>
  value
    ? `${String(value.hour).padStart(2, '0')}:${String(value.minute).padStart(2, '0')}`
    : null;

const publicHint = computed(() => (draft.is_private ? $t('meetings.privacy.internal_only') : props.isPublic ? $t('meetings.record.visible_public') : undefined));

const save = async () => {
  // eslint-disable-next-line vue/no-mutating-props -- Commit the draft to the parent-owned Inertia form on save.
  Object.assign(props.form, clone(draft));
  // Inertia recalculates isDirty through a watcher before saveThen decides whether to submit.
  await nextTick();

  // A rejected save never calls back, so the sheet stays open on its errors.
  props.saveThen(() => {
    baseline.value = JSON.stringify(draft);
    emit('update:open', false);
  });
};
</script>
