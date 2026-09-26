<template>
  <SheetForm
    :open
    :title="$t('meetings.agenda.sheet_title')"
    :description="$t('meetings.agenda.sheet_description')"
    :save-label="submitLabel"
    :processing
    :disabled="items.length === 0"
    :dirty="items.length > 0"
    @update:open="emit('update:open', $event)"
    @cancel="reset"
    @submit="submit"
  >
    <FormSegmentedControl
      v-model="mode"
      :options="modeOptions"
      :aria-label="$t('meetings.agenda.mode_label')"
      test-id-prefix="agenda-add-mode"
    />

    <AgendaItemsEditor v-if="mode === 'lines'" v-model="lines" />

    <FormFieldWrapper
      v-else-if="mode === 'paste'"
      id="agenda-paste"
      :label="$t('meetings.agenda.paste_label')"
      :hint="$t('meetings.agenda.paste_hint')"
    >
      <Textarea
        id="agenda-paste"
        v-model="pasted"
        rows="10"
        :class="['min-h-48 font-normal', fieldSurfaceClass]"
        placeholder="1. 10.00–10.30 …"
      />
    </FormFieldWrapper>

    <div v-else class="space-y-3">
      <p class="text-sm text-muted-foreground">
        {{ $t('meetings.agenda.previous_hint') }}
      </p>
      <div v-if="loadingRecent" class="space-y-2" aria-busy="true">
        <Skeleton v-for="index in 3" :key="index" class="h-14 w-full" />
      </div>
      <p v-else-if="!recentAgendas?.length" class="border-y border-border py-4 text-sm text-muted-foreground">
        {{ $t('meetings.agenda.previous_empty') }}
      </p>
      <ul v-else class="divide-y divide-border border-y border-border">
        <li v-for="recent in recentAgendas" :key="recent.id">
          <button
            type="button"
            class="flex w-full items-center justify-between gap-3 py-3 text-left transition-colors hover:bg-accent/60 pointer-coarse:min-h-11"
            @click="applyRecent(recent)"
          >
            <span class="min-w-0">
              <span class="block text-sm font-medium text-foreground">{{ formatRecentDate(recent.start_time) }}</span>
              <span class="block truncate text-xs text-muted-foreground">{{ recent.institution_name }}</span>
            </span>
            <span class="shrink-0 text-xs text-muted-foreground">
              {{ $t('meetings.agenda.previous_items', { count: String(recent.agenda_items.length) }) }}
            </span>
          </button>
        </li>
      </ul>
    </div>

    <p v-if="mode === 'paste' && items.length" class="text-sm font-medium text-foreground">
      {{ $t('meetings.agenda.paste_preview', { count: String(items.length) }) }}
    </p>
    <ol v-if="mode === 'paste' && items.length" class="divide-y divide-border border-y border-border text-sm">
      <li v-for="(item, index) in items" :key="index" class="flex gap-3 py-2">
        <span class="w-5 shrink-0 text-right tabular-nums text-muted-foreground">{{ index + 1 }}.</span>
        <span class="min-w-0 flex-1">{{ item.title }}</span>
        <span v-if="item.startTime" class="shrink-0 tabular-nums text-muted-foreground">
          {{ item.endTime ? `${item.startTime}–${item.endTime}` : item.startTime }}
        </span>
      </li>
    </ol>
  </SheetForm>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { getActiveLanguage, trans as $t } from 'laravel-vue-i18n';
import { ClipboardPaste, History, ListPlus } from 'lucide-vue-next';

import AgendaItemsEditor from '@/Components/ActionWindow/AgendaItemsEditor.vue';
import FormFieldWrapper from '@/Components/AdminForms/FormFieldWrapper.vue';
import { FormSegmentedControl, SheetForm, type FormSegmentOption } from '@/Components/Patterns';
import { fieldSurfaceClass } from '@/Components/ui/control';
import { Skeleton } from '@/Components/ui/skeleton';
import { Textarea } from '@/Components/ui/textarea';
import { formatDate } from '@/Utils/dateTime';
import { parseAgendaText, type ParsedAgendaLine } from '@/Utils/parseAgendaText';

export type AddAgendaMode = 'lines' | 'paste' | 'previous';

export interface RecentAgenda {
  id: string;
  start_time: string | null;
  institution_name: string;
  agenda_items: string[];
}

const props = defineProps<{
  open: boolean;
  meetingId: string;
  initialMode?: AddAgendaMode;
  /** `Inertia::optional` on the meeting page — fetched the first time the third mode is opened. */
  recentAgendas?: RecentAgenda[] | null;
}>();

const emit = defineEmits<{
  'update:open': [value: boolean];
}>();

const mode = ref<AddAgendaMode>(props.initialMode ?? 'lines');
const lines = ref<string[]>(['']);
const pasted = ref('');
const processing = ref(false);
const loadingRecent = ref(false);

const modeOptions = computed<FormSegmentOption<AddAgendaMode>[]>(() => [
  { value: 'lines', label: $t('meetings.agenda.mode_lines'), icon: ListPlus },
  { value: 'paste', label: $t('meetings.agenda.mode_paste'), icon: ClipboardPaste },
  { value: 'previous', label: $t('meetings.agenda.mode_previous'), icon: History },
]);

watch(() => [props.open, props.initialMode] as const, ([open, initialMode]) => {
  if (open && initialMode) {
    mode.value = initialMode;
  }
});

watch([mode, () => props.open], ([current, open]) => {
  if (open && current === 'previous' && props.recentAgendas == null && !loadingRecent.value) {
    loadingRecent.value = true;
    router.reload({
      only: ['recentAgendas'],
      onFinish: () => {
        loadingRecent.value = false;
      },
    });
  }
}, { immediate: true });

const items = computed<ParsedAgendaLine[]>(() => (mode.value === 'paste'
  ? parseAgendaText(pasted.value)
  : lines.value
      .map(title => title.trim())
      .filter(title => title !== '')
      .map(title => ({ title, startTime: null, endTime: null }))));

const submitLabel = computed(() => (items.value.length
  ? `${$t('meetings.agenda.submit')} (${items.value.length})`
  : $t('meetings.agenda.submit')));

const formatRecentDate = (value: string | null) => formatDate(value, { format: 'full', locale: getActiveLanguage() }) || '—';

/** A template is a starting point: it lands in the editable list, not straight on the agenda. */
const applyRecent = (recent: RecentAgenda) => {
  lines.value = recent.agenda_items.length ? [...recent.agenda_items] : [''];
  mode.value = 'lines';
};

const reset = () => {
  lines.value = [''];
  pasted.value = '';
};

const submit = () => {
  if (items.value.length === 0) {
    return;
  }

  processing.value = true;

  router.post(route('agendaItems.store'), {
    meeting_id: props.meetingId,
    agendaItemTitles: items.value.map(item => item.title),
    startTimes: items.value.map(item => item.startTime),
    endTimes: items.value.map(item => item.endTime),
  }, {
    preserveScroll: true,
    onSuccess: () => {
      reset();
      emit('update:open', false);
    },
    onFinish: () => {
      processing.value = false;
    },
  });
};
</script>
