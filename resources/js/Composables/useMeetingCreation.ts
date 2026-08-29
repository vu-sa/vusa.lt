/**
 * Meeting creation payload types and the single submit path to `meetings.store`.
 *
 * The step machinery this file used to carry lived alongside `NewMeetingDialog`;
 * navigation now belongs to the action window's screen stack
 * ({@link useActionWindow}), so what remains is the shape of the request and how
 * it is sent.
 */

import { ref, readonly } from 'vue';
import { router } from '@inertiajs/vue3';
import { trans as $t } from 'laravel-vue-i18n';

import type { MeetingTypeValue } from '@/Types/MeetingType';

export interface MeetingFormData {
  institution_id: string;
  start_time: string;
  type: MeetingTypeValue;
  description?: string;
  /** When true, a draft calendar announcement is created alongside the meeting. */
  announce_in_calendar?: boolean;
  /**
   * Not stored on the meeting: it asks the server to redirect into the meeting page's
   * bulk agenda dialog, where a whole timetable can be pasted at once.
   */
  open_bulk_agenda?: boolean;
}

export interface AgendaItemFormData {
  title: string;
  description?: string;
  order: number;
  brought_by_students?: boolean;
  start_time?: string | null;
  end_time?: string | null;
}

export interface SubmitMeetingPayload {
  meeting: Partial<MeetingFormData>;
  agendaItems: AgendaItemFormData[];
}

export interface SubmitMeetingCallbacks {
  onSuccess?: () => void;
  onError?: (errors: Record<string, string>) => void;
}

/**
 * A wall-clock string the server reads in the app timezone (Europe/Vilnius).
 *
 * `toISOString()` would send an instant in UTC, and Carbon would store that hour —
 * an 18:00 meeting saved as 15:00 in summer. The old MeetingForm sent a local
 * string for exactly this reason; the window has to as well.
 */
export function toLocalDateTime(date: Date): string {
  const pad = (value: number) => String(value).padStart(2, '0');

  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`
    + `T${pad(date.getHours())}:${pad(date.getMinutes())}:${pad(date.getSeconds())}`;
}

export function useMeetingCreation() {
  const submitting = ref(false);
  const errors = ref<Record<string, string>>({});

  /**
   * Posts through Inertia rather than the API: `MeetingController::store`
   * redirects to the new meeting, and letting that redirect happen is what
   * takes the user somewhere useful when the window closes.
   */
  const submitMeeting = ({ meeting, agendaItems }: SubmitMeetingPayload, callbacks: SubmitMeetingCallbacks = {}) => {
    if (submitting.value) {
      return;
    }

    submitting.value = true;
    errors.value = {};

    router.post(route('meetings.store'), {
      ...meeting,
      agendaItems: agendaItems.length > 0 ? agendaItems : undefined,
    }, {
      preserveScroll: true,
      onSuccess: () => {
        callbacks.onSuccess?.();
      },
      onError: (received) => {
        errors.value = Object.keys(received).length > 0
          ? received
          : { general: $t('Nepavyko sukurti susitikimo. Bandykite dar kartą.') };
        callbacks.onError?.(errors.value);
      },
      onFinish: () => {
        submitting.value = false;
      },
    });
  };

  return {
    submitting: readonly(submitting),
    errors: readonly(errors),
    submitMeeting,
  };
}
