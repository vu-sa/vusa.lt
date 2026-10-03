import { Circle, CircleCheck, CircleX, Info, TriangleAlert } from 'lucide-vue-next';

import { statusRoleClasses, type StatusRole } from '@/Constants/statuses';

export type BadgeTone = 'success' | 'danger' | 'warning' | 'info' | 'neutral' | 'related';

const TONE_ROLE: Record<BadgeTone, StatusRole> = {
  success: 'success',
  danger: 'danger',
  warning: 'attention',
  info: 'info',
  neutral: 'neutral',
  related: 'info',
};

export function toneClass(tone: BadgeTone): string {
  return statusRoleClasses[TONE_ROLE[tone]];
}

export function toneIcon(tone: BadgeTone) {
  switch (tone) {
    case 'success': return CircleCheck;
    case 'danger': return CircleX;
    case 'warning': return TriangleAlert;
    case 'info':
    case 'related': return Info;
    default: return Circle;
  }
}

/** VoteValue (positive/negative/neutral) → tone, for vote/decision/favorability. */
export function voteTone(value?: string | null): BadgeTone {
  switch (value) {
    case 'positive': return 'success';
    case 'negative': return 'danger';
    default: return 'neutral';
  }
}

/** Meeting completion_status → tone. */
export function completionTone(value?: string | null): BadgeTone {
  switch (value) {
    case 'complete': return 'success';
    case 'incomplete':
    case 'partial': return 'warning';
    default: return 'neutral';
  }
}

/** vote_alignment_status → tone. */
export function alignmentTone(value?: string | null): BadgeTone {
  switch (value) {
    case 'aligned':
    case 'all_match':
    case 'match': return 'success';
    case 'misaligned':
    case 'all_mismatch':
    case 'mismatch': return 'danger';
    case 'mixed': return 'warning';
    default: return 'neutral';
  }
}
