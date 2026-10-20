import { timeDay, timeFormat } from 'd3';

import type { InstitutionStatusHistoryPoint } from '../types';

export interface StatusFlowBucket {
  from: string;
  until: string;
  left_attention: number;
  entered_attention: number;
  meetings_registered: number;
  check_ins_registered: number;
}

export function statusFlowInterval(days: number, plotWidth = 600): number {
  const capacity = Math.max(1, Math.floor(plotWidth / 6));
  return Math.max(1, Math.ceil(days / capacity));
}

export function groupStatusFlows(data: InstitutionStatusHistoryPoint[], from: string, until: string, days: number, plotWidth = 600): StatusFlowBucket[] {
  const start = new Date(`${from}T00:00:00`);
  const end = new Date(`${until}T00:00:00`);
  const interval = statusFlowInterval(days, plotWidth);
  const format = timeFormat('%Y-%m-%d');
  const buckets = new Map<number, StatusFlowBucket>();

  for (const point of data) {
    const date = new Date(`${point.date}T00:00:00`);
    if (date < start || date > end) continue;
    const index = Math.floor(timeDay.count(start, date) / interval);
    const bucketStart = timeDay.offset(start, index * interval);
    const bucketEnd = timeDay.offset(bucketStart, interval - 1);
    const bucket = buckets.get(index) ?? {
      from: format(bucketStart), until: format(bucketEnd > end ? end : bucketEnd),
      left_attention: 0, entered_attention: 0, meetings_registered: 0, check_ins_registered: 0,
    };
    bucket.left_attention += point.left_attention ?? 0;
    bucket.entered_attention += point.entered_attention ?? 0;
    bucket.meetings_registered += point.meetings_registered ?? 0;
    bucket.check_ins_registered += point.check_ins_registered ?? 0;
    buckets.set(index, bucket);
  }

  return [...buckets.entries()].sort(([a], [b]) => a - b).map(([, bucket]) => bucket);
}
