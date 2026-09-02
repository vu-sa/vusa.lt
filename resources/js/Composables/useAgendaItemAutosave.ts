import { computed, ref } from 'vue';
import { watchDebounced } from '@vueuse/core';
import type { InertiaForm } from '@inertiajs/vue3';

export type VoteValue = 'positive' | 'negative' | 'neutral' | null;

/**
 * A Spatie-translatable field as the editor holds it. Read-only surfaces receive the
 * already-localized string instead (`App.Entities.*`), so this shape stays local to the
 * editor, which is the only place that writes translations.
 */
export interface TranslatedField {
  lt: string;
  en: string;
}

/** Server-shaped translations (`toFullArray()`) coerced into a complete `{lt, en}` pair. */
export function toTranslatedField(value: unknown): TranslatedField {
  // Spatie hands back `[]` for a field that has never been set in any locale.
  if (typeof value === 'object' && value !== null && !Array.isArray(value)) {
    const record = value as Record<string, unknown>;

    return {
      lt: typeof record.lt === 'string' ? record.lt : '',
      en: typeof record.en === 'string' ? record.en : '',
    };
  }

  return { lt: typeof value === 'string' ? value : '', en: '' };
}

export interface EditableVote {
  id?: string | null;
  is_main?: boolean;
  is_consensus?: boolean;
  title: TranslatedField;
  note: TranslatedField;
  student_vote?: VoteValue;
  decision?: VoteValue;
  student_benefit?: VoteValue;
  order?: number;
}

export interface AgendaItemFormData {
  title: TranslatedField;
  type: 'voting' | 'informational' | 'deferred' | 'break' | null;
  brought_by_students: boolean;
  student_position: TranslatedField;
  description: TranslatedField;
  /** Timetable slot as `HH:MM`; null when the body did not schedule per-item times. */
  start_time: string | null;
  end_time: string | null;
  votes: EditableVote[];
}

export type SaveStatus = 'idle' | 'saving' | 'saved' | 'dirty';

/**
 * Debounced auto-save for the agenda item editor.
 *
 * Auto-save is enabled by default and can be toggled off, in which case the
 * caller is expected to render an explicit save button bound to `submit()`.
 * `flush()` performs an immediate save of any pending changes — call it before
 * navigating away so edits are not lost.
 */
export function useAgendaItemAutosave(
  form: InertiaForm<AgendaItemFormData>,
  agendaItemId: string,
  debounceMs = 1500,
) {
  const autoSaveEnabled = ref(true);
  const lastSavedAt = ref<Date | null>(null);

  const submit = (onSaved?: () => void) => {
    if (form.processing) {
      return;
    }

    form
      .transform(data => ({
        ...data,
        votes: data.votes.map((vote, index) => ({ ...vote, order: index })),
      }))
      .patch(route('agendaItems.update', agendaItemId), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
          form.defaults();
          lastSavedAt.value = new Date();
          onSaved?.();
        },
      });
  };

  /**
   * Persist any pending changes, then run `callback`. If nothing is dirty the
   * callback runs immediately. Used to flush edits before navigating away.
   */
  const saveThen = (callback: () => void) => {
    if (form.isDirty && !form.processing) {
      submit(callback);
    }
    else {
      form.defaults();
      callback();
    }
  };

  watchDebounced(
    () => form.data(),
    () => {
      if (!autoSaveEnabled.value || !form.isDirty || form.processing) {
        return;
      }
      submit();
    },
    { debounce: debounceMs, deep: true },
  );

  const flush = () => {
    if (form.isDirty && !form.processing) {
      submit();
    }
  };

  const saveStatus = computed<SaveStatus>(() => {
    if (form.processing) {
      return 'saving';
    }
    if (form.isDirty) {
      return 'dirty';
    }
    if (lastSavedAt.value) {
      return 'saved';
    }
    return 'idle';
  });

  return { autoSaveEnabled, lastSavedAt, saveStatus, submit, flush, saveThen };
}
