import { computed } from 'vue';
import { getActiveLanguage } from 'laravel-vue-i18n';

import { MeetingType, getMeetingTypeOptions, type MeetingTypeValue } from '@/Types/MeetingType';

/**
 * Shared logic for meeting forms (create and edit)
 */
export function useMeetingForm() {
  // Get meeting type options based on current locale
  const meetingTypeOptions = computed(() => {
    const locale = getActiveLanguage() === 'en' ? 'en' : 'lt';
    return getMeetingTypeOptions(locale);
  });

  // Check if type is email meeting (date-only)
  const isEmailMeeting = (type: MeetingTypeValue | string | undefined | null): boolean => {
    if (type === '__null__' || type === null || type === undefined) return false;
    return type === MeetingType.Email;
  };

  // Check if date falls on a weekend
  const isWeekendTime = (date: Date | undefined | null): boolean => {
    if (!date) return false;
    const day = date.getDay();
    return day === 0 || day === 6; // Sunday or Saturday
  };

  // Format form values for submission
  const formatMeetingData = (values: { start_time: Date; type: MeetingTypeValue | null; description?: { lt: string; en: string } }): {
    start_time: string;
    type: MeetingTypeValue | null;
    description?: { lt: string; en: string };
  } => {
    const dt = values.start_time;
    const meetingType = values.type;

    // For email meetings, set time to 23:59:59 (deadline semantics)
    const adjustedDate = new Date(dt);
    if (isEmailMeeting(meetingType)) {
      adjustedDate.setHours(23, 59, 59, 0);
    }

    // Format date in local timezone without conversion to UTC
    const localISOString = new Date(adjustedDate.getTime() - (adjustedDate.getTimezoneOffset() * 60000))
      .toISOString()
      .slice(0, 19)
      .replace('T', ' ');

    return {
      start_time: localISOString,
      type: meetingType,
      ...(values.description !== undefined && { description: values.description }),
    };
  };

  /**
   * `description` arrives as a `{lt, en}` map from the admin payload, but as a plain
   * localized string from anywhere reading `toArray()` — and as `[]` when never set.
   */
  const toLocaleObject = (value: unknown): { lt: string; en: string } => {
    if (typeof value === 'object' && value !== null && !Array.isArray(value)) {
      const record = value as Record<string, unknown>;

      return {
        lt: typeof record.lt === 'string' ? record.lt : '',
        en: typeof record.en === 'string' ? record.en : '',
      };
    }

    return { lt: typeof value === 'string' ? value : '', en: '' };
  };

  // Get initial values from a meeting object
  const getInitialValues = (meeting: { start_time?: string | null; type?: MeetingTypeValue | null; description?: unknown }) => ({
    start_time: meeting?.start_time ? new Date(meeting.start_time) : undefined,
    type: meeting?.type ?? undefined, // Don't preselect any meeting type
    description: toLocaleObject(meeting?.description),
  });

  return {
    meetingTypeOptions,
    isEmailMeeting,
    isWeekendTime,
    formatMeetingData,
    getInitialValues,
  };
}
