export type TimelineScopeType = 'user' | 'duty' | 'institution';

export interface TimelineScope {
  type: TimelineScopeType;
  id: string;
  label?: string | null;
  sublabel?: string | null;
  institution_id?: string | null;
}

export interface TimelineGroup {
  key: string;
  kind: 'user' | 'duty';
  label: string;
  sublabel?: string | null;
  photo?: string | null;
  places_to_occupy?: number | null;
  cadence_id?: string | null;
}

export interface TimelineRow {
  id: string;
  group_key: string;
  duty_id: string;
  duty_name: string | null;
  institution_id: string | null;
  institution_name: string | null;
  holder_id: string;
  holder_name: string | null;
  holder_photo: string | null;
  tenant_id: number | null;
  tenant_shortname: string | null;
  /** The term the assignment spends most of itself in. Sorting only — never the filter. */
  cadence_id: string | null;
  /**
   * Every term the assignment's period touches. A re-elected member spans several, so
   * "did they serve in 2024–2025" is a membership test, not an equality one.
   */
  cadence_ids: string[];
  start_date: string;
  end_date: string | null;
  via_dutiable_id: string | null;
  /**
   * Per-assignment overrides beyond the dates. `null` when the row is only a period —
   * which is what makes a row with extras unsafe to merge away without looking.
   */
  extras: {
    email?: string;
    study_program?: string;
    study_program_note?: string;
    photo?: string;
    description?: string;
    original_duty_name?: boolean;
  } | null;
  source: { id: string; duty_name: string | null } | null;
  derived_ids: string[];
  is_derived: boolean;
  editable: boolean;
  edit_url: string;
}

export interface TimelineCadence {
  id: string;
  label: string;
  start_date: string;
  end_date: string;
  institution_id: string | null;
  is_global: boolean;
}

/**
 * A pending, unsaved move. `projected` marks a row the user did not touch: an ex-officio
 * seat mirroring a staged source, drawn so the knock-on effect is visible before commit.
 */
export interface StagedDates {
  start_date: string;
  end_date: string | null;
  projected?: boolean;
}

/** One entry of the operation list the backend planner folds. */
export interface TimelineOperation {
  type: 'set_dates' | 'align_to_cadence' | 'close_open_ended';
  row_ids: string[];
  start_date?: string | null;
  end_date?: string | null;
  /** Omitted means "align each edge to its own nearest term" — see PlanDutiableTimelineChanges. */
  cadence_id?: string | null;
  edges?: 'start' | 'end' | 'both';
  threshold_days?: number | null;
}

export interface TimelineDiagnostic {
  code: string;
  severity: 'error' | 'warning' | 'info';
  row_ids: string[];
  group_key?: string | null;
  duty_id?: string | null;
  detail?: Record<string, unknown>;
}

export interface TimelineChange {
  row_id: string;
  holder_id: string;
  holder_name: string | null;
  duty_name: string | null;
  before: { start_date: string; end_date: string | null };
  after: { start_date: string; end_date: string | null };
  reasons: string[];
  derived: Array<{ id: string; duty_name: string | null; start_date: string; end_date: string | null }>;
  blocked: string | null;
}

export interface TimelinePlanPayload {
  changes: TimelineChange[];
  unchanged_row_ids: string[];
  diagnostics_before: TimelineDiagnostic[];
  diagnostics_after: TimelineDiagnostic[];
  summary: { changed: number; blocked: number; unchanged: number; derived: number };
  self_affecting: boolean;
}

export interface TimelinePayload {
  scope: TimelineScope;
  groups: TimelineGroup[];
  rows: TimelineRow[];
  cadences: TimelineCadence[];
  cadence_defaults: { start_month_day: string; end_month_day: string };
  diagnostics: TimelineDiagnostic[];
  meta: { row_count: number; truncated: boolean; max_rows: number };
}

/** A row with its dates parsed once, so renderers never re-parse per frame. */
export interface ParsedRow extends TimelineRow {
  startDate: Date;
  /** `null` means open-ended — the bar runs to the right edge with an arrow. */
  endDate: Date | null;
}

export interface ParsedCadence extends TimelineCadence {
  startDate: Date;
  endDate: Date;
}

/**
 * A drawable lane. `type` is deliberately a plain string so the shared
 * `renderBackground` helper accepts it; only `'tenant'` is treated as a header.
 */
export interface TimelineLayoutRow {
  key: string;
  type: 'tenant' | 'row';
  top: number;
  height: number;
  group: TimelineGroup;
  row?: ParsedRow;
}
