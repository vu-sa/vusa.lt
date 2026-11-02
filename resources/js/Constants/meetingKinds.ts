import { trans as $t } from 'laravel-vue-i18n';

import { MeetingKind } from '@/Types/enums';

/** Option labels for the type and institution forms; titles themselves are built server-side. */
export const meetingKindLabel = (kind: MeetingKind): string => $t(`forms.options.meeting_kind_${kind}`);

export const meetingKindOptions = (): Array<{ value: MeetingKind; label: string }> =>
  Object.values(MeetingKind).map(kind => ({ value: kind, label: meetingKindLabel(kind) }));

/**
 * Mirrors Institution::resolvedMeetingKind() for an institution without an override: the first of
 * its types, by id, that declares a kind.
 */
export const meetingKindFromTypes = (
  typeIds: number[],
  types: Array<{ id: number; extra_attributes?: Record<string, unknown> | null }>,
): MeetingKind => {
  const declared = types
    .filter(type => typeIds.includes(type.id))
    .sort((a, b) => a.id - b.id)
    .map(type => type.extra_attributes?.meeting_kind)
    .find((kind): kind is MeetingKind => Object.values(MeetingKind).includes(kind as MeetingKind));

  return declared ?? MeetingKind.Sitting;
};
