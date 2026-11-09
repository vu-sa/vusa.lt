import { LearningImportance, LearningLayer, LearningStage, LearningStepKind } from '@/Types/enums';
import type { LearningPathData, LearningStepData } from '@/Types/learning';

export function step(overrides: Partial<LearningStepData> = {}): LearningStepData {
  return {
    id: 'step-1', key: null, isSystem: false, kind: LearningStepKind.Knowledge,
    layer: LearningLayer.Foundation, layerLabel: 'Pagrindas', stage: LearningStage.FirstMonths,
    importance: LearningImportance.Recommended, title: 'Read the guide', description: 'Prepare to represent students.',
    source: null, actionUrl: null, state: 'pending', autoCompleted: false, completedAt: null,
    skippable: true, insightsCount: 2, reviewedAt: null, glossaryTerms: [], ...overrides,
  };
}

export function path(): LearningPathData {
  return {
    currentStage: LearningStage.FirstMonths, termStart: '2026-10-01',
    stages: [
      { stage: LearningStage.BeforeStart, steps: [step({ id: 'before', state: 'done' })] },
      { stage: LearningStage.FirstMonths, steps: [step()] },
      { stage: LearningStage.WholeTerm, steps: [] },
      { stage: LearningStage.TermEnd, steps: [step({ id: 'end' })] },
    ],
    meetingPrep: [{ institutionType: { id: 1, title: 'SPK' }, steps: [step({ id: 'prep', kind: LearningStepKind.MeetingPrep, title: 'Read the agenda' })] }],
    totals: { essentialDone: 0, essentialTotal: 0, allDone: 1, allTotal: 3 },
  };
}
