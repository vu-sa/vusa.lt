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

/** A blank vote; marking one as main is the editor's choice (see getOutcomeVotes()). */
export function createVote(): EditableVote {
  return {
    id: null,
    is_main: false,
    is_consensus: false,
    title: { lt: '', en: '' },
    note: { lt: '', en: '' },
    student_vote: null,
    decision: null,
    student_benefit: null,
  };
}

export interface AgendaItemFormData {
  title: TranslatedField;
  type: 'voting' | 'informational' | 'deferred' | 'break' | 'withdrawn' | null;
  is_private: boolean;
  public_title: TranslatedField;
  brought_by_students: boolean;
  student_position: TranslatedField;
  description: TranslatedField;
  /** Timetable slot as `HH:MM`; null when the body did not schedule per-item times. */
  start_time: string | null;
  end_time: string | null;
  votes: EditableVote[];
}

/** The item as the server sends it to an editor: every translation, with its votes. */
export interface EditableAgendaItemSource {
  title?: unknown;
  public_title?: unknown;
  student_position?: unknown;
  description?: unknown;
  is_private?: boolean | null;
  type?: string | null;
  brought_by_students?: boolean | null;
  start_time?: string | null;
  end_time?: string | null;
  votes?: Array<{
    id?: string | null;
    is_main?: boolean | null;
    is_consensus?: boolean | null;
    title?: unknown;
    note?: unknown;
    student_vote?: string | null;
    decision?: string | null;
    student_benefit?: string | null;
    order?: number | null;
  }>;
}

export function agendaItemFormData(item: EditableAgendaItemSource): AgendaItemFormData {
  return {
    title: toTranslatedField(item.title),
    is_private: item.is_private ?? false,
    public_title: toTranslatedField(item.public_title),
    type: (item.type ?? null) as AgendaItemFormData['type'],
    brought_by_students: item.brought_by_students ?? false,
    student_position: toTranslatedField(item.student_position),
    description: toTranslatedField(item.description),
    // The columns are TIME (`HH:MM:SS`); the inputs and the validator both speak `HH:MM`.
    start_time: item.start_time ? String(item.start_time).slice(0, 5) : null,
    end_time: item.end_time ? String(item.end_time).slice(0, 5) : null,
    votes: (item.votes ?? []).map((vote): EditableVote => ({
      id: vote.id,
      is_main: vote.is_main ?? false,
      is_consensus: vote.is_consensus ?? false,
      title: toTranslatedField(vote.title),
      note: toTranslatedField(vote.note),
      student_vote: (vote.student_vote ?? null) as VoteValue,
      decision: (vote.decision ?? null) as VoteValue,
      student_benefit: (vote.student_benefit ?? null) as VoteValue,
      order: vote.order ?? 0,
    })),
  };
}

export type SaveStatus = 'idle' | 'saving' | 'saved' | 'dirty';

/**
 * Debounced auto-save for the agenda item editor.
 *
 * `flush()` performs an immediate save of any pending changes — call it before
 * navigating away so edits are not lost.
 */
export function useAgendaItemAutosave(
  form: InertiaForm<AgendaItemFormData>,
  agendaItemId: string,
  debounceMs = 1500,
  /** Deferred props a save cannot change: reloading them swaps their panels for skeletons and loses the scroll. */
  except: string[] = [],
) {
  const lastSavedAt = ref<Date | null>(null);
  /** Callbacks waiting for the save in flight (and any edits made during it) to land. */
  const pending: Array<() => void> = [];

  const snapshot = () => JSON.stringify(form.data());

  const submit = (onSaved?: () => void) => {
    if (onSaved) {
      pending.push(onSaved);
    }

    if (form.processing) {
      return;
    }

    // Edits made while the request is in flight must stay dirty, so the saved
    // snapshot — not the form as it is on success — becomes the new baseline.
    const sent = snapshot();
    let saved = false;

    form
      .transform(data => ({
        ...data,
        votes: data.votes.map((vote, index) => ({ ...vote, order: index })),
      }))
      .patch(route('agendaItems.update', agendaItemId), {
        preserveScroll: true,
        preserveState: true,
        except,
        onSuccess: () => {
          saved = true;
          form.defaults(JSON.parse(sent) as AgendaItemFormData);
          lastSavedAt.value = new Date();
        },
        onFinish: () => {
          if (!saved) {
            // A rejected save stays on screen with its errors; never navigate away from it.
            pending.length = 0;
            return;
          }
          if (snapshot() !== sent) {
            submit();
            return;
          }
          pending.splice(0).forEach(callback => callback());
        },
      });
  };

  /**
   * Persist any pending changes, then run `callback` — including when a save is
   * already in flight. Used to flush edits before navigating away.
   */
  const saveThen = (callback: () => void) => {
    if (form.isDirty || form.processing) {
      submit(callback);
      return;
    }

    callback();
  };

  watchDebounced(
    () => form.data(),
    () => {
      if (!form.isDirty || form.processing) {
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

  return { lastSavedAt, saveStatus, submit, flush, saveThen };
}
