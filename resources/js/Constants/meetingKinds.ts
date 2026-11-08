import { trans as $t } from 'laravel-vue-i18n';

import { MeetingKind } from '@/Types/enums';

export const meetingKindLabel = (kind: MeetingKind): string => $t(`forms.options.meeting_kind_${kind}`);

export const meetingKindOptions = (): Array<{ value: MeetingKind; label: string }> =>
  Object.values(MeetingKind).map(kind => ({ value: kind, label: meetingKindLabel(kind) }));
