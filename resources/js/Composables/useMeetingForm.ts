import { computed } from 'vue';
import { trans as $t, transChoice as $tChoice, getActiveLanguage } from 'laravel-vue-i18n';
import { toTypedSchema } from '@vee-validate/zod';
import * as z from 'zod';

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

  // Base schema for meeting forms
  const baseSchema = toTypedSchema(
    z.object({
      start_time: z.date({
        required_error: $t('validation.required', { attribute: $t('forms.fields.date') }),
      }),
      type: z.string({
        required_error: $t('validation.required', { attribute: $tChoice('forms.fields.type', 0) }),
      }).nullable(),
    }),
  );

  // Extended schema with description
  const extendedSchema = toTypedSchema(
    z.object({
      start_time: z.date({
        required_error: $t('validation.required', { attribute: $t('forms.fields.date') }),
      }),
      type: z.string({
        required_error: $t('validation.required', { attribute: $tChoice('forms.fields.type', 0) }),
      }).nullable(),
      description: z.string().optional(),
    }),
  );

  // Format form values for submission
  const formatMeetingData = (values: Record<string, any>): {
    start_time: string;
    type: MeetingTypeValue;
    description?: string;
  } => {
    const dt = values.start_time as Date;
    const meetingType = values.type as MeetingTypeValue;

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

  // Get initial values from a meeting object
  const getInitialValues = (meeting: any) => ({
    start_time: meeting?.start_time ? new Date(meeting.start_time) : undefined,
    type: meeting?.type ?? undefined, // Don't preselect any meeting type
    description: meeting?.description || '',
  });

  return {
    meetingTypeOptions,
    isEmailMeeting,
    isWeekendTime,
    baseSchema,
    extendedSchema,
    formatMeetingData,
    getInitialValues,
  };
}
