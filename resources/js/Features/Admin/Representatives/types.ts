import type { InstitutionActivityStatus } from '@/Types/enums';

/** Mirrors `RepresentativesPresenter` (RepRow, RepSummary). */
export type RepActivity = 'week' | 'month' | 'stale' | 'never';

export type RepAttentionReason = 'never_logged_in' | 'institution_overdue' | 'essentials_not_started' | 'inactive';

export interface RepInstitution {
  id: string;
  name: string;
  tenant: string | null;
  status: InstitutionActivityStatus;
  lastMeetingAt: string | null;
  nextMeetingAt: string | null;
}

export interface RepLearning {
  essentialDone: number;
  essentialTotal: number;
  allDone: number;
  allTotal: number;
}

export interface RepRow {
  id: string;
  name: string;
  email: string;
  photo: string | null;
  institutions: RepInstitution[];
  termStart: string | null;
  incoming: boolean;
  lastAction: string | null;
  activity: RepActivity;
  meetings: { recorded: number; held: number };
  /** Null unless the viewer coordinates this rep: progress never follows padalinys rights. */
  learning: RepLearning | null;
  attention: { reason: RepAttentionReason; institution: string | null } | null;
}

export interface RepSummary {
  total: number;
  incoming: number;
  attention: number;
  activity: Record<RepActivity, number>;
  recordedOwn: number;
  learning: { reps: number; finished: number; inProgress: number; notStarted: number } | null;
}

export interface RepresentativesOverview {
  reps: RepRow[];
  summary: RepSummary;
}

export type RepListFilter = 'all' | 'attention' | 'incoming' | RepActivity;
