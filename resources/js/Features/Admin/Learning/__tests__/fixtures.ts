import { LearningAudienceType, LearningImportance, LearningStage, LearningStepKind } from '@/Types/enums';
import type { LearningAudienceEditData, LearningPathData, LearningStepData, LearningStepEditData, KnowledgeItemData, KnowledgeItemEditData } from '@/Types/learning';

export function step(overrides: Partial<LearningStepData> = {}): LearningStepData {
  return {
    id: 'step-1', key: null, isSystem: false, kind: LearningStepKind.Action,
    audienceLabel: 'Pagrindas', audienceNotes: [], stage: LearningStage.FirstMonths,
    importance: LearningImportance.Recommended, title: 'Read the guide', description: 'Prepare to represent students.',
    actionUrl: null, actionLabel: null, state: 'pending', autoCompleted: false, automaticallyTracked: false, completedAt: null,
    skippable: true, reviewedAt: null, ...overrides,
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
    knowledge: { essentialDone: 1, essentialTotal: 2, done: 2, total: 4, nextItem: knowledge({ id: 'document-next' }) },
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
    kind: LearningStepKind.Action, audiences: [audience()],
    per_term: false, order: 1, is_active: true, is_system: false, ...overrides,
  };
}

export function knowledge(overrides: Partial<KnowledgeItemData> = {}): KnowledgeItemData {
  return {
    id: 'knowledge-1', title: 'Read the guide', abbreviation: '', summary: 'What the guide covers.', description: 'Prepare to represent students.',
    audienceLabel: 'Pagrindas', audienceNotes: [], section: null, importance: LearningImportance.Recommended,
    source: null, state: 'pending', autoCompleted: false, completedAt: null,
    skippable: true, insightsCount: 2, reviewedAt: null, perTerm: false, ...overrides,
  };
}

export function editKnowledge(overrides: Partial<KnowledgeItemEditData> = {}): KnowledgeItemEditData {
  return {
    id: 'knowledge-1', title: { lt: 'LT pavadinimas', en: 'EN title' }, abbreviation: { lt: '', en: '' },
    summary: { lt: 'LT santrauka', en: 'EN summary' }, description: { lt: '', en: '' },
    knowledge_section_id: null, tenant_id: null, audiences: [audience({ stage: null })], per_term: false, order: 1,
    is_active: true, sources: {}, source_documents: {}, ...overrides,
  };
}
