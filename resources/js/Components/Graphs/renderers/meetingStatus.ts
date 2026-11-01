import { trans as $t } from 'laravel-vue-i18n';

import { meetingAlignmentStatuses, meetingCompletionStatuses, statusFor, statusRoleParts } from '@/Constants/statuses';

interface GanttMeetingStatus {
  authorized?: boolean;
  completion_status?: string | null;
  vote_alignment_status?: string | null;
}

/** The one word for a Gantt meeting: an unfinished record first, then how the vote went. */
export function ganttMeetingStatus(meeting: GanttMeetingStatus): { label: string; tone: string } | null {
  if (meeting.authorized === false) {
    return { label: $t('Nėra prieigos'), tone: 'text-muted-foreground' };
  }

  const status = meeting.completion_status === 'complete'
    ? statusFor(meetingAlignmentStatuses, meeting.vote_alignment_status) ?? meetingCompletionStatuses.complete
    : statusFor(meetingCompletionStatuses, meeting.completion_status);

  return status ? { label: $t(status.label), tone: statusRoleParts[status.role].text } : null;
}
