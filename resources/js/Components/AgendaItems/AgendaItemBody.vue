<template>
  <!-- The same controls in both modes, merely locked when not editing: swapping in a
       read-only rendering used to shift the whole page as the toggle flipped. -->
  <div class="space-y-8">
    <div class="rounded-xl border border-zinc-200 bg-zinc-50/70 dark:bg-zinc-900/40 p-4 sm:p-5 dark:border-zinc-800">
      <div class="flex flex-wrap items-start justify-between gap-x-10 gap-y-6">
        <div class="min-w-0 flex-1 basis-64 space-y-3">
          <span class="flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-muted-foreground">
            <ListChecks class="h-3.5 w-3.5" />
            {{ $t('Klausimo tipas') }}
          </span>
          <div class="flex flex-wrap items-center gap-1.5">
            <button
              v-for="option in typeOptions"
              :key="option.value"
              type="button"
              :disabled="!editing"
              class="flex items-center gap-1.5 rounded-md border px-3.5 py-2 text-sm font-medium transition-colors disabled:cursor-default"
              :class="form.type === option.value
                ? 'border-primary bg-primary text-primary-foreground'
                : 'border-zinc-200 bg-white dark:bg-zinc-950/40 text-zinc-500 enabled:hover:border-zinc-400 enabled:hover:text-foreground disabled:opacity-60 dark:border-zinc-700 dark:text-zinc-400 dark:enabled:hover:border-zinc-500'"
              @click="form.type = option.value"
            >
              <component :is="option.icon" class="h-4 w-4 shrink-0" />
              {{ option.label }}
            </button>
            <button
              v-if="form.type && editing"
              type="button"
              class="rounded-md px-2 py-2 text-sm text-muted-foreground hover:text-destructive"
              :title="$t('Išvalyti')"
              @click="form.type = null"
            >
              <X class="h-4 w-4" />
            </button>
          </div>
        </div>

        <div class="min-w-0 space-y-3">
          <span class="flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-muted-foreground">
            <Clock class="h-3.5 w-3.5" />
            {{ $t('Laikas') }}
          </span>
          <div class="flex flex-wrap items-center gap-2">
            <!-- TimePicker rather than <input type="time">: the native control follows the browser's
                 locale and shows AM/PM for anyone whose machine is set to English. -->
            <TimePicker
              :model-value="startTimeValue"
              :minute-step="5"
              clearable
              :disabled="!editing"
              class="h-9 w-[6.5rem] text-sm"
              :title="$t('Kada klausimas pradedamas svarstyti')"
              @update:model-value="(value) => form.start_time = toTimeString(value)"
            />
            <span class="text-muted-foreground">–</span>
            <TimePicker
              :model-value="endTimeValue"
              :minute-step="5"
              clearable
              :disabled="!editing"
              class="h-9 w-[6.5rem] text-sm"
              :title="$t('Kada klausimo svarstymas baigiamas')"
              @update:model-value="(value) => form.end_time = toTimeString(value)"
            />
          </div>
          <p v-if="form.errors.end_time" class="text-xs text-destructive">
            {{ form.errors.end_time }}
          </p>
        </div>
      </div>

      <label class="mt-6 flex w-fit items-center gap-2.5" :class="editing ? 'cursor-pointer' : ''">
        <Switch v-model="form.brought_by_students" :disabled="!editing" />
        <span class="text-sm text-zinc-700 dark:text-zinc-300">{{ $t('Atstovų iškeltas klausimas') }}</span>
      </label>
    </div>

    <AgendaItemVotes
      v-if="form.type === 'voting'"
      :form
      :editing
      :requires-student-perspective
    />

    <!-- Description + student position -->
    <div class="rounded-xl border border-zinc-200 bg-zinc-50/70 dark:bg-zinc-900/40 p-4 sm:p-5 dark:border-zinc-800">
      <AgendaItemTextTabs
        :editable="editing"
        :description="form.description"
        :student-position="form.student_position"
        :show-student-position="requiresStudentPerspective"
        @update:description="(v) => form.description = v"
        @update:student-position="(v) => form.student_position = v"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import type { InertiaForm } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';
import { CalendarClock, Clock, Info, ListChecks, Vote, X } from 'lucide-vue-next';

import { Switch } from '@/Components/ui/switch';
import { TimePicker, type TimeValue } from '@/Components/ui/time-picker';
import AgendaItemTextTabs from '@/Components/AgendaItems/AgendaItemTextTabs.vue';
import AgendaItemVotes from '@/Components/AgendaItems/AgendaItemVotes.vue';
import type { AgendaItemFormData } from '@/Composables/useAgendaItemAutosave';

const props = withDefaults(defineProps<{
  form: InertiaForm<AgendaItemFormData>;
  editing?: boolean;
  /**
   * False for VU SA's own bodies: the representatives *are* the organisation, so there is no
   * separate student position or student benefit to record — only the outcome.
   */
  requiresStudentPerspective?: boolean;
}>(), {
  editing: false,
  requiresStudentPerspective: true,
});

const typeOptions = [
  { value: 'voting' as const, label: $t('Balsavimas'), icon: Vote },
  { value: 'informational' as const, label: $t('Informacinis'), icon: Info },
  { value: 'deferred' as const, label: $t('Atidėtas'), icon: CalendarClock },
];

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

const startTimeValue = computed(() => toTimeValue(props.form.start_time));
const endTimeValue = computed(() => toTimeValue(props.form.end_time));
</script>
