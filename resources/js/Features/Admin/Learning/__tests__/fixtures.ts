import { LearningAudienceType, LearningImportance, LearningStage, LearningStepKind } from '@/Types/enums';
import type { LearningAudienceEditData, LearningPathData, LearningStepData, LearningStepEditData } from '@/Types/learning';

export function step(overrides: Partial<LearningStepData> = {}): LearningStepData {
  return {
    id: 'step-1', key: null, isSystem: false, kind: LearningStepKind.Knowledge,
    audienceLabel: 'Pagrindas', audienceNotes: [], section: null, stage: LearningStage.FirstMonths,
    importance: LearningImportance.Recommended, title: 'Read the guide', description: 'Prepare to represent students.',
    source: null, actionUrl: null, actionLabel: null, state: 'pending', autoCompleted: false, automaticallyTracked: false, completedAt: null,
    skippable: true, insightsCount: 2, reviewedAt: null, glossaryTerms: [], ...overrides,
  };
}

export function path(): LearningPathData {
  return {
    currentStage: LearningStage.FirstMonths, termStart: '2026-10-01',
    stages: [
      { stage: LearningStage.BeforeStart, steps: [step({ id: 'before', kind: LearningStepKind.Action, state: 'done' })] },
      { stage: LearningStage.FirstMonths, steps: [step({ kind: LearningStepKind.Action })] },
      { stage: LearningStage.WholeTerm, steps: [] },
      { stage: LearningStage.TermEnd, steps: [step({ id: 'end', kind: LearningStepKind.Action })] },
    ],
    meetingPrep: [{ audience: { id: 'audience-spk', label: 'SPK' }, steps: [step({ id: 'prep', kind: LearningStepKind.MeetingPrep, stage: null, title: 'Read the agenda' })] }],
    documents: { essentialDone: 1, essentialTotal: 2, done: 2, total: 4, nextDocument: step({ id: 'document-next' }) },
    totals: { essentialDone: 0, essentialTotal: 0, allDone: 1, allTotal: 3 },
  };
}

export function audience(overrides: Partial<LearningAudienceEditData> = {}): LearningAudienceEditData {
  return {
    id: 'audience-1', type: LearningAudienceType.Everyone, source_id: null, tenant_id: null,
    importance: LearningImportance.Essential, stage: LearningStage.BeforeStart,
    description: { lt: '', en: '' }, order: 0, label: 'Visi', ...overrides,
  };
}

export function editStep(overrides: Partial<LearningStepEditData> = {}): LearningStepEditData {
  return {
    id: 'step-1', title: { lt: 'LT pavadinimas', en: 'EN title' },
    description: { lt: 'LT paaiškinimas', en: 'EN explanation' },
    kind: LearningStepKind.Knowledge, learning_section_id: null, audiences: [audience()],
    per_term: false, order: 1, is_active: true, is_system: false, sources: {}, ...overrides,
  };
}
