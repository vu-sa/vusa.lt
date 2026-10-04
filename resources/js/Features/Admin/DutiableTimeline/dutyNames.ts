import type { TimelineChange, TimelineRow } from './types';

import { changeDutyNameEndings } from '@/Utils/String';

export function timelineDutyName(row: TimelineRow | TimelineChange, locale: string): string {
  return changeDutyNameEndings(
    { name: row.holder_name }, row.duty_name ?? '—', locale,
    row.holder_pronouns, row.use_original_duty_name,
  );
}

export function timelineSourceDutyName(row: TimelineRow, locale: string): string {
  return changeDutyNameEndings(
    { name: row.holder_name }, row.source?.duty_name ?? '—', locale,
    row.holder_pronouns, row.source?.use_original_duty_name,
  );
}
