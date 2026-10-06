/** Goals pilot (App\Support\Experiments\GoalsExperiment). */
export interface Translated {
  lt: string;
  en: string;
}

export interface PersonRef {
  id: string;
  name: string;
}

export interface GoalStep {
  id: string;
  title: Translated;
  description: Translated | null;
  happened_on: string;
  goal_id: string | null;
  problem_id: string | null;
  url?: string | null;
  agenda_item_id?: string | null;
  document_id?: number | null;
  goal?: { id: string; title: string } | null;
  problem?: { id: string; title: string } | null;
  /** Who wrote the step down (StepResource). */
  recorder?: PersonRef | null;
  /** Who did it. */
  performers?: PersonRef[];
  /** Null when the step has none, or the reader cannot open it. */
  agenda_item?: { id: string; title: string; start_time: string | null; institutions: string[] } | null;
  document?: { id: number; title: string } | null;
}

export interface StepParent {
  type: 'goal' | 'problem';
  id: string;
}

export interface LinkOption {
  id: string;
  title: string;
}

export const translatedText = (value: Partial<Translated> | null | undefined, locale: string): string =>
  (locale === 'en' ? value?.en || value?.lt : value?.lt || value?.en) ?? '';

export interface LinkedGoal {
  id: string;
  title: string;
  status: import('@/Types/enums').GoalStatus;
  tenant: string;
  /** Set where unlinking depends on rights on the goal (agenda item page). */
  can_update?: boolean;
}

export interface ProblemGoalLinks {
  goals: LinkedGoal[];
  steps: GoalStep[];
}
