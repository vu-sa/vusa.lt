/**
 * MeetingType enum values matching App\Enums\MeetingType
 */
export const MeetingType = {
  InPerson: 'in-person',
  Remote: 'remote',
  Email: 'email',
} as const;

export type MeetingTypeValue = typeof MeetingType[keyof typeof MeetingType] | null;

export interface MeetingTypeOption {
  value: MeetingTypeValue;
  label: string;
  isDateOnly: boolean;
}

/**
 * Get meeting type options for forms.
 * Includes "Other" option which maps to null.
 */
export function getMeetingTypeOptions(locale: 'lt' | 'en' = 'lt'): MeetingTypeOption[] {
  const labels = {
    lt: {
      [MeetingType.InPerson]: 'Gyvas susitikimas',
      [MeetingType.Remote]: 'Nuotolinis susitikimas',
      [MeetingType.Email]: 'Elektroninis posėdis (el. laišku)',
      other: 'Kita',
    },
    en: {
      [MeetingType.InPerson]: 'In-person Meeting',
      [MeetingType.Remote]: 'Remote Meeting',
      [MeetingType.Email]: 'E-meeting (via email)',
      other: 'Other',
    },
  };

  return [
    {
      value: MeetingType.InPerson,
      label: labels[locale][MeetingType.InPerson],
      isDateOnly: false,
    },
    {
      value: MeetingType.Remote,
      label: labels[locale][MeetingType.Remote],
      isDateOnly: false,
    },
    {
      value: MeetingType.Email,
      label: labels[locale][MeetingType.Email],
      isDateOnly: true,
    },
    {
      value: null,
      label: labels[locale].other,
      isDateOnly: false,
    },
  ];
}

/**
 * Check if meeting type is date-only (no time picker needed).
 */
export function isDateOnlyMeetingType(value: MeetingTypeValue): boolean {
  return value === MeetingType.Email;
}
